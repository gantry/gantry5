<?php
namespace Gantry\Framework\Base;

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
    public function add_to_context(array $context)
    {
        $gantry = \Gantry\Framework\Gantry::instance();
        $context['site'] = $gantry['site'];
        $context['config'] = $gantry['config'];
        $context['theme'] = $this;

        // Include Gantry specific things to the context.
        $file = JsonFile::instance($this->path . '/layouts/test.json');
        $context['pageSegments'] = $file->content();

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

        foreach ($locator->findResources('theme://engine') as $path) {
            $loader->addPath($path, 'nucleus');
        }
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
