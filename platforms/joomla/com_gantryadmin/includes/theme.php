<?php
namespace Gantry\Admin;

use Gantry\Component\Twig\TwigExtension;
use Gantry\Framework\Base\Theme as BaseTheme;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Symfony\Component\Yaml\Yaml;

class Theme extends BaseTheme
{
    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = \JUri::root(true) . '/templates/' . $this->name;
    }

    public function add_to_context(array $context)
    {
        $gantry = \Gantry\Framework\Gantry::instance();
        $context['config'] = $gantry['config'];
        $context['theme'] = $this;

        return $context;
    }

    public function add_to_twig(\Twig_Environment $twig, \Twig_Loader_Filesystem $loader = null)
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        if (!$loader) {
            $loader = $twig->getLoader();
        }
        $loader->setPaths($locator->findResources('gantry-admin://templates'));

        $twig->addExtension(new TwigExtension);
        return $twig;
    }

    public function render($file, array $context = array())
    {
        $loader = new \Twig_Loader_Filesystem($this->path . '/templates');

        $params = array(
            'cache' => JPATH_CACHE . '/gantry5admin/twig',
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
        $sitename = $app->get('sitename');

        // Add JavaScript Frameworks
        \JHtml::_('bootstrap.framework');
        // Load optional RTL Bootstrap CSS
        \JHtml::_('bootstrap.loadCss', false, $this->direction);

        return $twig->render($file, $context);
    }
}
