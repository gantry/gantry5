<?php
namespace Gantry\Framework;

use Gantry\Framework\Base\Theme as BaseTheme;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends BaseTheme
{
    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = \JUri::root(true) . '/templates/' . $this->name;
    }

    public function render($file, array $context = array())
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader = new \Twig_Loader_Filesystem($locator->findResources('gantry-engine://templates'));

        $params = array(
            'cache' => $locator('gantry-cache://') . '/twig',
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => false
        );

        $twig = new \Twig_Environment($loader, $params);

        $this->add_to_twig($twig);

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        $doc = \JFactory::getDocument();
        $this->language = $doc->language;
        $this->direction = $doc->direction;

        // Add JavaScript Frameworks
        \JHtml::_('bootstrap.framework');
        // Load optional RTL Bootstrap CSS
        \JHtml::_('bootstrap.loadCss', false, $this->direction);

        return $twig->render($file, $context);
    }

    public function widgets_init()
    {
        $gantry = Gantry::instance();
        $positions = (array) $gantry['config']->get('positions');

        foreach ($positions as $name => $params) {
            $params = (array) $params;
            if (!isset($params['name'])) {
                $params['name'] = ucfirst($name);
            }
            // Register position.
        }
    }
}
