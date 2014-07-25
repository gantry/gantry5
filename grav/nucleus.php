<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Common\Registry;
use Grav\Common\Filesystem\File;

class Nucleus extends Theme
{
    /**
     * Initialize nucleus layout engine.
     */
    public function onAfterTwigInit()
    {
        $env = Registry::get('Twig');
        $twig = $env->twig();
        $loader = $env->loader();

        $twig->addFilter('toGrid', new \Twig_Filter_Function(array($this, 'toGrid')));
        $loader->addPath( __DIR__ . '/nucleus', 'nucleus' );
    }

    /**
     * Load current layout.
     */
    public function onAfterSiteTwigVars()
    {
        $file = File\Json::instance(__DIR__ . '/test/nucleus.json');

        $twig = Registry::get('Twig');
        $twig->twig_vars['pageSegments'] = $file->content();
    }

    /**
     * Twig filter.
     *
     * @param $text
     * @return string
     */
    public function toGrid($text) {
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
