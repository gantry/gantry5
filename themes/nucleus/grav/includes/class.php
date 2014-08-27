<?php
namespace Grav\Theme;

use Grav\Common\Registry;
use Gantry\Framework\Theme as GantryTheme;

class Nucleus extends GantryTheme
{
    /**
     * @return array
     */
    public static function getSubscribedEvents() {
        return [
            'onTwigInitialized' => ['onTwigInitialized', 0],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ];
    }

    /**
     * Initialize nucleus layout engine.
     */
    public function onTwigInitialized()
    {
        $env = $this->grav['twig'];
        $this->add_to_twig($env->twig(), $env->loader());
    }

    /**
     * Load current layout.
     */
    public function onTwigSiteVariables()
    {
        $twig = $this->grav['twig'];
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
