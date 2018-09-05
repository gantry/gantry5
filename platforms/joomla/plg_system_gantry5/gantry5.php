<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

class plgSystemGantry5 extends JPlugin
{
    /**
     * @var JApplicationCms
     */
    protected $app;
    protected $styles;
    protected $modules;

    public function __construct(&$subject, $config = array())
    {
        $this->_name = isset($config['name']) ? $config['name'] : 'gantry5';
        $this->_type = isset($config['type']) ? $config['type'] : 'system';

        $this->app = JFactory::getApplication();

        $this->loadLanguage('plg_system_gantry5.sys');

        JLoader::register('Gantry5\Loader', JPATH_LIBRARIES . '/gantry5/Loader.php');

        // Detect Gantry Framework or fail gracefully.
        if (!class_exists('Gantry5\Loader')) {
            if ($this->app->isAdmin()) {
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
     * Return global configuration for Gantry5.
     */
    public function onGantryGlobalConfig(&$global)
    {
        $global = $this->params->toArray();
    }

    public function onAfterRoute()
    {
        if ($this->app->isSite()) {
            $this->onAfterRouteSite();

        } elseif ($this->app->isAdmin()) {
            $this->onAfterRouteAdmin();
        }
    }

    public function onAfterRender()
    {
        if ($this->app->isSite() && class_exists('Gantry\Framework\Gantry')) {
            $this->onAfterRenderSite();

        } elseif ($this->app->isAdmin()) {
            $this->onAfterRenderAdmin();
        }
    }

    /**
     * @param object $module
     * @param array $attribs
     */
    public function onRenderModule(&$module, &$attribs)
    {
        if (!$this->app->isSite() || !class_exists('Gantry\Framework\Gantry')) {
            return;
        }

        $gantry = \Gantry\Framework\Gantry::instance();
        $outline = $gantry['configuration'];

        // Do not render modules assigned to menu items in error and offline page.
        if (isset($module->menuid) && $module->menuid > 0 && in_array($outline, array('_error', '_offline'))) {
            $module = null;
        }

        // TODO: This event allows more diverse module assignment conditions.
    }

    /**
     * Serve particle AJAX requests in 'index.php?option=com_ajax&plugin=particle&format=json'.
     *
     * @return array|string|null
     */
    public function onAjaxParticle()
    {
        if (!$this->app->isSite() || !class_exists('Gantry\Framework\Gantry')) {
            return null;
        }

        $input = $this->app->input;
        $format = $input->getCmd('format', 'html');

        if (!in_array($format, ['json', 'raw', 'debug'])) {
            throw new RuntimeException(JText::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $props = $_GET;
        unset($props['option'], $props['plugin'], $props['format'], $props['id'], $props['Itemid']);

        $identifier = $input->getCmd('id');

        if (strpos($identifier, 'module-') === 0) {
            preg_match('`-([\d]+)$`', $input->getCmd('id'), $matches);

            if (!isset($matches[1])) {
                throw new RuntimeException(JText::_('JERROR_PAGE_NOT_FOUND'), 404);
            }

            $id = $matches[1];

            require_once JPATH_ROOT . '/modules/mod_gantry5_particle/helper.php';

            return ModGantry5ParticleHelper::ajax($id, $props, $format);
        }

        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var \Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];
        $layout = $theme->loadLayout();
        $html = '';

        if ($identifier === 'main-particle') {
            $type = $identifier;
            $menu = $this->app->getMenu();
            $menuItem = $menu->getActive();
            $params = $menuItem ? $menuItem->getParams() : new JRegistry;

            /** @var object $params */
            $data = json_decode($params->get('particle'), true);
            if ($data && $theme->hasContent()) {
                $context = [
                    'gantry' => $gantry,
                    'noConfig' => true,
                    'inContent' => true,
                    'ajax' => $props,
                    'segment' => [
                        'id' => $identifier,
                        'type' => $data['type'],
                        'classes' => $params->get('pageclass_sfx'),
                        'subtype' => $data['particle'],
                        'attributes' => $data['options']['particle'],
                    ]
                ];

                $html = trim($theme->render("@nucleus/content/particle.html.twig", $context));
            }
        } else {
            $particle = $layout->find($identifier);
            if (!isset($particle->type) || $particle->type !== 'particle') {
                throw new RuntimeException(JText::_('JERROR_PAGE_NOT_FOUND'), 404);
            }

            $context = array(
                'gantry' => $gantry,
                'inContent' => false,
                'ajax' => $props,
            );

            $block = $theme->getContent($particle, $context);
            $type = $particle->type . '.' . $particle->subtype;
            $html = (string) $block;
        }

        if ($format === 'raw') {
            return $html;
        }

        return ['code' => 200, 'type' => $type, 'id' => $identifier, 'props' => (object) $props, 'html' => $html];
    }

    /**
     * Load Gantry framework before dispatching to the component.
     */
    private function onAfterRouteSite()
    {
        $input = $this->app->input;

        $templateName = $this->app->getTemplate();

        if (!$this->isGantryTemplate($templateName)) {
            return;
        }

        $gantryPath = JPATH_THEMES . "/{$templateName}/includes/gantry.php";

        if (is_file($gantryPath)) {
            // Manually setup Gantry 5 Framework from the template.
            $gantry = include $gantryPath;

            if (!$gantry) {
                throw new \RuntimeException(
                    JText::sprintf("GANTRY5_THEME_LOADING_FAILED", $templateName, JText::_('GANTRY5_THEME_INCLUDE_FAILED')),
                    500
                );
            }

        } else {

            // Setup Gantry 5 Framework or throw exception.
            Gantry5\Loader::setup();

            // Get Gantry instance.
            $gantry = Gantry\Framework\Gantry::instance();

            // Initialize the template.
            $gantry['theme.path'] = JPATH_THEMES . "/{$templateName}";
            $gantry['theme.name'] = $templateName;

            $themePath = $gantry['theme.path'] . '/includes/theme.php';

            include_once $themePath;
        }

        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Using Gantry 5 template {$templateName}");

        /** @var Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];

        $assignments = new \Gantry\Framework\Assignments();

        if (GANTRY_DEBUGGER) {
            \Gantry\Debugger::addMessage('Selecting outline (rules, matches, scores):', 'debug');
            \Gantry\Debugger::addMessage($assignments->getPage(), 'debug');
            \Gantry\Debugger::addMessage($assignments->loadAssignments(), 'debug');
            \Gantry\Debugger::addMessage($assignments->matches(), 'debug');
            \Gantry\Debugger::addMessage($assignments->scores(), 'debug');
        }

        $theme->setLayout($assignments->select());

        if (!$this->params->get('production', 0) || $this->params->get('asset_timestamps', 1)) {
            $age = (int) ($this->params->get('asset_timestamps_period', 7) * 86400);
            Gantry\Framework\Document::$timestamp_age = $age > 0 ? $age : PHP_INT_MAX;
        } else {
            Gantry\Framework\Document::$timestamp_age = 0;
        }
    }

    /**
     * Re-route Gantry templates to Gantry Administration component.
     */
    private function onAfterRouteAdmin()
    {
        $input = $this->app->input;

        $option = $input->getCmd('option');
        $task   = $input->getCmd('task');

        if (in_array($option, array('com_templates', 'com_advancedtemplates')) && $task && strpos($task, 'style') === 0) {
            // Get all ids.
            $cid = $input->post->get('cid', (array) $input->getInt('id'), 'array');

            if ($cid) {
                $styles = $this->getStyles();
                $selected = array_intersect_key($styles, array_flip($cid));

                // If no Gantry templates were selected, just let com_templates deal with the request.
                if (!$selected) {
                    return;
                }

                // Special handling for tasks coming from com_template.
                if ($task === 'style.edit') {
                    $theme = reset($selected);
                    $id = key($selected);
                    $token = JSession::getFormToken();
                    $this->app->redirect("index.php?option=com_gantry5&view=configurations/{$id}/styles&theme={$theme}&{$token}=1");
                }
            }
        }
    }

    /**
     * Convert all stream uris into proper links.
     */
    private function onAfterRenderSite()
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        $html = $this->app->getBody();

        // Only filter our streams. If there's an error (bad UTF8), fallback with original output.
        $this->app->setBody($gantry['document']->urlFilter($html, false, 0, true) ?: $html);
    }

    /**
     * Convert links in com_templates to point into Gantry Administrator component.
     */
    private function onAfterRenderAdmin()
    {
        $document = JFactory::getDocument();
        $type   = $document->getType();

        $option = $this->app->input->getString('option');
        $view   = $this->app->input->getString('view', 'g5');
        $task   = $this->app->input->getString('task');

        if (in_array($option, array('com_templates', 'com_advancedtemplates')) && ($view == 'g5' || $view == 'styles') && !$task && $type == 'html') {
            $this->styles = $this->getStyles();

            $body = preg_replace_callback('/(<a\s[^>]*href=")([^"]*)("[^>]*>)(.*)(<\/a>)/siU', array($this, 'appendHtml'), $this->app->getBody());

            $this->app->setBody($body);
        }

        if (($option == 'com_modules' || $option == 'com_advancedmodules') && (($view == 'g5' || $view == 'modules') || empty($view)) && $type == 'html') {
            $db    = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query->select('id, title, params');
            $query->from('#__modules');
            $query->where('module = ' . $db->quote('mod_gantry5_particle'));
            $db->setQuery($query);
            $data = $db->loadObjectList();

            if (count($data) > 0) {
                $this->modules = array();
                $body = $this->app->getBody();

                foreach ($data as $module) {
                    $params   = json_decode($module->params);
                    $particle = isset($params->particle) ? json_decode($params->particle) : '';
                    $title = isset($particle->title) ? $particle->title : (isset($particle->particle) ? $particle->particle : '');
                    $type = isset($particle->particle) ? $particle->particle : '';

                    $this->modules[$module->id] = $particle;

                    $body = preg_replace_callback('/(<a\s[^>]*href=")([^"]*)("[^>]*>)(.*)(<\/a>)/siU', function($matches) use ($title, $type) {
                        return $this->appendHtml($matches, $title, $type);
                    }, $body);
                }


                $this->app->setBody($body);
            }
        }
    }

    /**
     * Save plugin parameters and trigger the save events.
     *
     * @param array $data
     * @return bool
     * @see JModelAdmin::save()
     */
    public function onGantry5SaveConfig(array $data)
    {
        $name = 'plg_' . $this->_type . '_' . $this->_name;

        // Initialise variables;
        $dispatcher = JEventDispatcher::getInstance();
        $table = JTable::getInstance('Extension');

        // Include the content plugins for the on save events.
        JPluginHelper::importPlugin('extension');

        // Load the row if saving an existing record.
        $table->load(array('type'=>'plugin', 'folder'=>$this->_type, 'element'=>$this->_name));

        $params = new Joomla\Registry\Registry($table->params);
        $params->loadArray($data);

        $table->params = $params->toString();

        // Check the data.
        if (!$table->check()) {
            throw new RuntimeException($table->getError());
        }

        // Trigger the onContentBeforeSave event.
        $result = $dispatcher->trigger('onExtensionBeforeSave', array($name, $table, false));
        if (in_array(false, $result, true)) {
            throw new RuntimeException($table->getError());
        }

        // Store the data.
        if (!$table->store()) {
            throw new RuntimeException($table->getError());
        }

        // Clean the cache.
        \Gantry\Joomla\CacheHelper::cleanPlugin();

        // Update plugin settings.
        $this->params = $params;

        // Trigger the onExtensionAfterSave event.
        $dispatcher->trigger('onExtensionAfterSave', array($name, $table, false));

        return true;
    }

    public function onContentBeforeSave($context, $table, $isNew)
    {
        if ($context !== 'com_menus.item') {
            return;
        }
    }

    public function onContentAfterSave($context, $table, $isNew)
    {
        if ($context !== 'com_menus.item') {
            return;
        }
    }

    public function onContentBeforeDelete($context, $table)
    {
        if ($context !== 'com_menus.item') {
            return;
        }
    }

    public function onContentAfterDelete($context, $table)
    {
        if ($context !== 'com_menus.item') {
            return;
        }
    }

    public function onExtensionBeforeSave($context, $table, $isNew)
    {
        if ($context === 'com_config.component' && $table && $table->type === 'component' && $table->name === 'com_gantry5') {
            $name = 'plg_' . $this->_type . '_' . $this->_name;

            $params = new Joomla\Registry\Registry($table->params);

            $data = (array) $params->get($name);

            Gantry5\Loader::setup();

            $this->onGantry5SaveConfig($data);

            // Do not save anything into the component itself (Joomla cannot handle it).
            $table->params = '';

            return;
        }
    }

    public function onExtensionAfterSave($context, $table, $isNew)
    {
        if ($context === 'com_config.component' && $table && $table->type === 'component' && $table->name === 'com_gantry5') {

        }

        if ($context !== 'com_templates.style' || $table->client_id || !$this->isGantryTemplate($table->template)) {
            return;
        }

        if (!$isNew) {
            return;
        }

        $template = $table->template;

        $this->load($template);
        $registry = new Joomla\Registry\Registry($table->params);
        $old = (int) $registry->get('configuration', 0);
        $new = (int) $table->id;

        if ($old && $old !== $new) {
            Gantry\Joomla\StyleHelper::copy($table, $old, $new);
        }
    }

    public function onExtensionBeforeDelete($context, $table)
    {
        if ($context !== 'com_templates.style' || $table->client_id || !$this->isGantryTemplate($table->template)) {
            return true;
        }

        $template = $table->template;

        $gantry = $this->load($template);

        /** @var \Gantry\Framework\Outlines $outlines */
        $outlines = $gantry['outlines'];

        try {
            $outlines->delete($table->id, false);
        } catch (Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        return true;
    }

    public function onContentPrepareData($context, $data)
    {
        $name = 'plg_' . $this->_type . '_' . $this->_name;

        // Check that we are manipulating a valid form.
        switch ($context) {
            case 'com_menus.item':
                break;
        }

        return true;
    }

    public function onContentPrepareForm($form, $data)
    {
        // Check that we are manipulating a valid form.
        if (!($form instanceof JForm)) {
            $this->_subject->setError('JERROR_NOT_A_FORM');

            return false;
        }

        $name = 'plg_' . $this->_type . '_' . $this->_name;

        switch ($form->getName()) {
            case 'com_config.component':
                // If we are editing configuration from Gantry component, add missing fields from system plugin.
                $rules = $form->getField('rules');
                if ($rules && $rules->getAttribute('component') == 'com_gantry5') {
                    $this->loadLanguage("{$name}.sys");
                    // Add plugin fields to the form under plg_type_name.
                    $file = file_get_contents(__DIR__."/{$this->_name}.xml");
                    $file = preg_replace('/ name="params"/', " name=\"{$name}\"", $file);
                    $form->load($file, false, '/extension/config');

                    // Joomla seems to be missing support for component data manipulation so do it manually here.
                    $form->bind([$name => $this->params->toArray()]);
                }
                break;

            case 'com_menus.items.filter':
                break;

            case 'com_menus.item':
                break;
        }

        return true;
    }

    /**
     * @param array  $matches
     * @param string $content
     *
     * @param string $type
     *
     * @return string
     */
    private function appendHtml(array $matches, $content = 'Gantry 5', $type = '')
    {
        $html = $matches[0];

        if (strpos($matches[2], 'task=style.edit') || strpos($matches[2], 'task=module.edit')) {
            $uri = new JUri($matches[2]);
            $id = (int) $uri->getVar('id');

            if ($id && in_array($uri->getVar('option'), array('com_templates', 'com_advancedtemplates', 'com_modules', 'com_advancedmodules')) && (isset($this->styles[$id]) || isset($this->modules[$id]))) {
                $html = $matches[1] . $uri . $matches[3] . $matches[4] . $matches[5];
                $colors = $content ? 'background:#439a86;' : 'background:#f17f48;';
                $content = $content ?: 'No Particle Selected';
                $title = $type ? ' title="Particle Type: ' . $type . '"' : '';

                $html .= ' <span class="label" ' . $title . ' style="' . $colors . 'color:#fff;">' . $content . '</span>';

                if (isset($this->modules[$id])) { unset($this->modules[$id]); }
                else { unset($this->styles[$id]); }
            }
        }

        return $html;
    }

    /**
     * @return array
     */
    private function getStyles()
    {
        static $list;

        if ($list === null) {
            // Load styles
            $db = JFactory::getDbo();
            $query = $db
                ->getQuery(true)
                ->select('s.id, s.template')
                ->from('#__template_styles as s')
                ->where('s.client_id = 0')
                ->where('e.enabled = 1')
                ->leftJoin('#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

            $db->setQuery($query);
            $templates = (array)$db->loadObjectList();

            $list = array();

            foreach ($templates as $template) {
                if ($this->isGantryTemplate($template->template)) {
                    $list[$template->id] = $template->template;
                }
            }
        }

        return $list;
    }

    private function isGantryTemplate($name)
    {
        return file_exists(JPATH_SITE . "/templates/{$name}/gantry/theme.yaml");
    }

    protected function load($name)
    {
        Gantry5\Loader::setup();

        $gantry = \Gantry\Framework\Gantry::instance();

        if (!isset($gantry['theme.name']) || $name !== $gantry['theme.name']) {
            // Restart Gantry and initialize it.
            $gantry = Gantry\Framework\Gantry::restart();
            $gantry['theme.name'] = $name;
            $gantry['streams']->register();

            $patform = $gantry['platform'];
            $locator = $gantry['locator'];

            // Initialize theme stream.
            $details = new Gantry\Component\Theme\ThemeDetails($name);
            $locator->addPath('gantry-theme', '', $details->getPaths(), false, true);

            // Initialize theme cache stream.
            $cachePath = $patform->getCachePath() . '/' . $name;
            Gantry\Component\FileSystem\Folder::create($cachePath);
            $locator->addPath('gantry-cache', 'theme', array($cachePath), true, true);

            \Gantry\Component\File\CompiledYamlFile::$defaultCachePath = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
            \Gantry\Component\File\CompiledYamlFile::$defaultCaching = $gantry['global']->get('compile_yaml', 1);
        }

        return $gantry;
    }
}
