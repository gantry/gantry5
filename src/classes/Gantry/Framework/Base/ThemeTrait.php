<?php
namespace Gantry\Framework\Base;

use Gantry\Component\Layout\LayoutReader;
use Gantry\Component\Twig\TwigExtension;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class ThemeTrait
 * @package Gantry\Framework\Base
 *
 * @property string $path
 */
trait ThemeTrait
{
    public function init()
    {
        $gantry = \Gantry\Framework\Gantry::instance();
        $gantry['streams'];
    }

    public function add_to_context(array $context)
    {
        $gantry = \Gantry\Framework\Gantry::instance();
        $context['gantry'] = $gantry;
        $context['site'] = $gantry['site'];
        $context['config'] = $gantry['config'];
        $context['theme'] = $this;

        // Include Gantry specific things to the context.
        $context['pageSegments'] = $gantry['layout'];

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
        $loader->setPaths($locator->findResources('theme://engine'), 'nucleus');

        $twig->addExtension(new TwigExtension);
        $twig->addFilter('toGrid', new \Twig_Filter_Function(array($this, 'toGrid')));
        return $twig;
    }

    public function toGrid($text)
    {
        static $sizes = array(
            '10'      => 'size-1-10',
            '20'      => 'size-1-5',
            '25'      => 'size-1-4',
            '33.3334' => 'size-1-3',
            '50'      => 'size-1-2',
            '100'     => ''
        );

        return isset($sizes[$text]) ? ' ' . $sizes[$text] : '';
    }
}
