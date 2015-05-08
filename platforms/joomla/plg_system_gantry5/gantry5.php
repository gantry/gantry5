<?php
defined('_JEXEC') or die;

class plgSystemGantry5 extends JPlugin
{
    /**
     * @var JApplicationCms
     */
    protected $app;
    protected $styles;

    public function __construct(&$subject, $config = array())
    {
        $this->app = JFactory::getApplication();

        JLoader::register('Gantry5\Loader', JPATH_LIBRARIES . '/gantry5/Loader.php');

        // Detect Gantry Framework or fail gracefully.
        if (!class_exists('Gantry5\Loader')) {
            if ($this->app->isAdmin()) {
                $this->loadLanguage('plg_system_gantry5.sys');
                $this->app->enqueueMessage(
                    JText::sprintf('PLG_SYSTEM_GANTRY5_LIBRARY_MISSING', JText::_('PLG_SYSTEM_GANTRY5')),
                    'warning'
                );
            }
            return;
        }

        parent::__construct($subject, $config);
}

    /**
     * Re-route Gantry templates to Gantry Administration component.
     */
    public function onAfterRoute()
    {
        if ($this->app->isSite()) {
            $this->onAfterRouteSite();

        } elseif ($this->app->isAdmin()) {
            $this->onAfterRouteAdmin();
        }
    }

    /**
     * Convert links in com_templates to point into Gantry Administrator component.
     */
    public function onAfterRender()
    {
        if (!$this->app->isAdmin()) {
            return;
        }

        $document = JFactory::getDocument();
        $type   = $document->getType();

        $option = $this->app->input->getString('option');
        $view   = $this->app->input->getString('view', 'styles');
        $task   = $this->app->input->getString('task');

        if ($option == 'com_templates' && $view == 'styles' && !$task && $type == 'html') {
            $this->styles = $this->getStyles();

            $body = preg_replace_callback('/(<a\s[^>]*href=")([^"]*)("[^>]*>)(.*)(<\/a>)/siU', array($this, 'appendHtml'), $this->app->getBody());

            $this->app->setBody($body);
        }
    }

    /**
     * Load Gantry framework before dispatching to the component.
     */
    private function onAfterRouteSite()
    {
        $template = $this->app->getTemplate(true);

        if (!file_exists(JPATH_THEMES . "/{$template->template}/gantry/theme.yaml")) {
            return;
        }

        $path = JPATH_THEMES . "/{$template->template}/includes/gantry.php";

        if (is_file($path)) {
            // Manually setup Gantry 5 Framework from the template.
            include $path;

            return;
        }

        // Setup Gantry 5 Framework or throw exception.
        Gantry5\Loader::setup();

        // Get Gantry instance.
        $gantry = Gantry\Framework\Gantry::instance();

        // Initialize the template.
        $gantry['theme.path'] = JPATH_THEMES . "/{$template->template}";
        $gantry['theme.name'] = $template->template;

        $themePath = $gantry['theme.path'] . '/includes/theme.php';

        //if (is_file($themePath)) {
        include_once $themePath;
    }

    /**
     * Re-route Gantry templates to Gantry Administration component.
     */
    private function onAfterRouteAdmin()
    {
        $input = $this->app->input;

        $option = $input->getCmd('option');
        $task   = $input->getCmd('task');

        if ($option == 'com_templates' && $task && strpos($task, 'style') === 0) {
            // Get all ids.
            $cid = $input->post->get('cid', (array) $input->getInt('id'), 'array');

            if ($cid) {
                $styles = $this->getStyles();
                $selected = array_intersect(array_keys($styles), $cid);

                // If no Gantry templates were selected, just let com_templates deal with the request.
                if (!$selected) {
                    return;
                }

                // Special handling for tasks coming from com_template.
                if ($task == 'style.edit') {
                    $id = (int) array_shift($cid);

                    if (isset($styles[$id])) {
                        $token = JSession::getFormToken();
                        $this->app->redirect("index.php?option=com_gantry5&view=configurations/{$id}/styles&style={$id}&{$token}=1");
                    }
                }
            }
        }
    }

    /**
     * @param array $matches
     * @return string
     */
    private function appendHtml(array $matches)
    {
        $html = $matches[0];

        if (strpos($matches[2], 'task=style.edit')) {
            $uri = new JUri($matches[2]);
            $id = (int) $uri->getVar('id');

            if ($id && $uri->getVar('option') == 'com_templates' && isset($this->styles[$id])) {
                $html = $matches[1] . $uri . $matches[3] . $matches[4] . $matches[5];
                $html .= ' <span class="label" style="background:#439a86;color:#fff;">Gantry 5</span>';
            }
        }

        return $html;
    }

    /**
     * @return array
     */
    private function getStyles()
    {
        $cache = JFactory::getCache('com_templates', '');
        $list = $cache->get('gantry-templates');

        if ($list === false) {
            // Load styles
            $db    = JFactory::getDbo();
            $query = $db
                ->getQuery(true)
                ->select('s.id, s.template, s.params')
                ->from('#__template_styles as s')
                ->where('s.client_id = 0')
                ->where('e.enabled = 1')
                ->leftJoin('#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

            $db->setQuery($query);
            $templates = (array) $db->loadObjectList();

            $list = array();

            foreach ($templates as $template) {
                if (file_exists(JPATH_SITE . "/templates/{$template->template}/gantry/theme.yaml")) {
                    $params = new \Joomla\Registry\Registry;
                    $params->loadString($template->params);

                    $list[$template->id] = true;
                }
            }

            $cache->store($list, 'gantry-templates');
        }

        return $list;
    }
}
