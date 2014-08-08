<?php
namespace Gantry\Framework\Base;

use Gantry\Component\Filesystem\File;

abstract class Theme
{
    public $name;
    public $url;
    public $path;

    public function __construct($path, $name = '')
    {
        if (!is_dir($path)) {
            throw new \LogicException('Theme not found!');
        }

        $this->path = $path;
        $this->name = $name ? $name : basename($path);
    }

    abstract public function render($file, array $context = array());

    public function add_to_context(array $context)
    {
        $gantry = \Gantry\Framework\Gantry::instance();
        $context['site'] = $gantry['site'];
        $context['config'] = $gantry['config'];
        $context['theme'] = $this;

        // Include Gantry specific things to the context.
        $file = File\Json::instance($this->path . '/layouts/test.json');
        $context['pageSegments'] = $file->content();

        return $context;
    }

    public function add_to_twig(\Twig_Environment $twig, \Twig_Loader_Filesystem $loader = null)
    {
        /* this is where you can add your own functions to twig */
        if (!$loader) {
            $loader = $twig->getLoader();
        }
        $loader->addPath($this->path . '/engine', 'nucleus');
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
