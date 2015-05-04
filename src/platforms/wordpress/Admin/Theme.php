<?php
namespace Gantry\Admin;

use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Filesystem\Streams;
use Gantry\Component\Twig\TwigExtension;
use Gantry\Framework\Base\Theme as BaseTheme;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends BaseTheme
{
    public $path;

    public function __construct( $path, $name = '' )
    {
        parent::__construct($path, $name);

        $this->boot();
    }

    protected function boot()
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        $relpath = Folder::getRelativePath($this->path);

        /** @var Streams $streams */
        $streams = $gantry['streams'];
        $streams->add(['gantry-admin' => [
                'prefixes' => [
                    '' => ['gantry-theme://admin', $relpath, $relpath . '/common'],
                    'assets/' => ['gantry-theme://admin', $relpath, $relpath . '/common']
                ]
            ]
            ]);
        $streams->register();
    }

    public function render($file, array $context = array())
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader = new \Twig_Loader_Filesystem($locator->findResources('gantry-admin://templates'));

        $params = array(
            'cache' => $locator->findResource('gantry-cache://twig', true, true),
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

    public function add_to_twig(\Twig_Environment $twig, \Twig_Loader_Filesystem $loader = null)
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        if (!$loader) {
            $loader = $twig->getLoader();
        }
        $loader->setPaths($locator->findResources('gantry-admin://templates'), 'gantry-admin');

        $twig->addExtension(new TwigExtension);
        return $twig;
    }

    public function add_to_context( array $context )
    {
        $context = parent::add_to_context( $context );

        $this->url = $context['site']->theme->link;

        return $context;
    }
}
