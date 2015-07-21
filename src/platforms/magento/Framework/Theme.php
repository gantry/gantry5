<?php
namespace Gantry\Framework;

use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends Base\Theme
{
    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = \Mage::getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_SKIN);
    }

    public function render($file, array $context = array())
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader = new \Twig_Loader_Filesystem($locator->findResources('gantry-theme://gantry/twig'));

        $params = array(
            'cache' => $locator->findResource('gantry-cache://theme/twig', true, true),
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => 'html'
        );

        $twig = new \Twig_Environment($loader, $params);

        $this->add_to_twig($twig);

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        return $twig->render($file, $context);
    }
}
