<?php
namespace Grav\Theme;

use Grav\Common\Registry;
use Grav\Common\Filesystem\File;
use Gantry\Framework\Theme;

class Nucleus extends Theme
{
    /**
     * Initialize nucleus layout engine.
     */
    public function onAfterTwigInit()
    {
        $env = Registry::get('Twig');
        $this->add_to_twig($env->twig(), $env->loader());
    }

    /**
     * Load current layout.
     */
    public function onAfterSiteTwigVars()
    {
        $twig = Registry::get('Twig');
        $twig->twig_vars = $this->add_to_context($twig->twig_vars);
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
