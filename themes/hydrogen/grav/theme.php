<?php
namespace Grav\Theme;

use Gantry\Framework\Theme as GantryTheme;
use Grav\Common\Theme;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Hydrogen extends Theme
{
    /**
     * @var GantryTheme
     */
    protected $theme;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onThemeInitialized' => ['onThemeInitialized', 0],
            'onTwigInitialized' => ['onTwigInitialized', 0],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ];
    }

    public function onThemeInitialized()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->grav['locator'];

        // Bootstrap Gantry framework or fail gracefully.
        $gantry = include_once $locator('theme://includes/gantry.php');
        if (!$gantry) {
            throw new \RuntimeException('Gantry Framework could not be loaded.');
        }

        // Define the template.
        require $locator('theme://includes/theme.php');

        // Define Gantry services.
        $path = $locator('theme://');
        $name = $this->name;
        $gantry['theme'] = function () use ($path, $name) {
            return new \Gantry\Theme\Hydrogen($path, $name);
        };

        /** @var \Gantry\Framework\Theme $theme */
        $this->theme = $gantry['theme'];
        $this->theme->setLayout('default');
    }

    /**
     * Initialize nucleus layout engine.
     */
    public function onTwigInitialized()
    {
        $env = $this->grav['twig'];
        $this->theme->extendTwig($env->twig(), $env->loader());
    }

    /**
     * Load current layout.
     */
    public function onTwigSiteVariables()
    {
        $twig = $this->grav['twig'];
        $twig->twig_vars = $this->theme->getContext($twig->twig_vars);
    }
}
