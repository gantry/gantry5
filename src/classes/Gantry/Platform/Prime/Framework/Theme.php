<?php
namespace Gantry\Framework;

use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends Base\Theme
{
    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = dirname($_SERVER['SCRIPT_NAME']);
    }

    public function render($file, array $context = array())
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $search = array_merge($locator->findResources('gantry-theme://twig'), [PRIME_ROOT . '/pages']);

        $loader = new \Twig_Loader_Filesystem($search);
        $loader->setPaths([PRIME_ROOT . '/positions'], 'positions');

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

        return $twig->render($file, $context);
    }
}
