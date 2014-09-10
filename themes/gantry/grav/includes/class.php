<?php
namespace Grav\Theme;

use Gantry\Framework\Theme as GantryTheme;

class Gantry extends GantryTheme
{
    /**
     * @var GantryTheme
     */
    protected $gantryTheme;

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
}
