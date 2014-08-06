<?php
namespace Gantry\Framework;

use Symfony\Component\Yaml\Yaml;

class Theme extends Base\Theme
{
    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = \JUri::root(true) . '/templates/' . $this->name;
    }

    public function render($file, array $context = array())
    {
        $loader = new \Twig_Loader_Filesystem($this->path . '/twig');

        $params = array(
            'cache' => JPATH_CACHE . '/gantry5/twig',
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => false
        );

        $twig = new \Twig_Environment($loader, $params);

        $this->add_to_twig($twig);

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        // Getting params from template
        $app = \JFactory::getApplication();
        $doc = \JFactory::getDocument();

        $params = $app->getTemplate(true)->params;

        $this->language = $doc->language;
        $this->direction = $doc->direction;

        // Detecting Active Variables
        $option   = $app->input->getCmd('option', '');
        $view     = $app->input->getCmd('view', '');
        $layout   = $app->input->getCmd('layout', '');
        $task     = $app->input->getCmd('task', '');
        $itemid   = $app->input->getCmd('Itemid', '');
        $sitename = $app->getCfg('sitename');

        // Add JavaScript Frameworks
        \JHtml::_('bootstrap.framework');
        // Load optional RTL Bootstrap CSS
        \JHtml::_('bootstrap.loadCss', false, $this->direction);

        return $twig->render($file, $context);
    }

    public function widgets_init()
    {
        $gantry = \Gantry\Gantry::instance();
        $positions = (array) $gantry->config()->get('positions');

        foreach ($positions as $name => $params) {
            $params = (array) $params;
            if (!isset($params['name'])) {
                $params['name'] = ucfirst($name);
            }
            // Register position.
        }
    }
}
