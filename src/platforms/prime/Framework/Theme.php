<?php
namespace Gantry\Framework;

use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends Base\Theme
{
    protected $renderer;

    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = dirname($_SERVER['SCRIPT_NAME']);
    }

    public function renderer()
    {
        if (!$this->renderer) {
            $gantry = \Gantry\Framework\Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $loader = new \Twig_Loader_Filesystem($locator->findResources('gantry-engine://twig'));
            $loader->setPaths($locator->findResources('gantry-pages://'), 'pages');
            $loader->setPaths($locator->findResources('gantry-positions://'), 'positions');

            $params = array(
                'cache' => $locator->findResource('gantry-cache://theme/twig', true, true),
                'debug' => true,
                'auto_reload' => true,
                'autoescape' => 'html'
            );

            $twig = new \Twig_Environment($loader, $params);

            $this->add_to_twig($twig);

            $this->renderer = $twig;
        }

        return $this->renderer;
    }

    public function render($file, array $context = array())
    {
        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        return $this->renderer()->render($file, $context);
    }
}
