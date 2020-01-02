<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2020 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Theme\AbstractTheme;
use Gantry\Component\Theme\ThemeTrait;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

/**
 * Class Theme
 * @package Gantry\Framework
 */
class Theme extends AbstractTheme
{
    use ThemeTrait;

    /** @var string */
    public $url;

    /**
     * @see AbstractTheme::init()
     */
    protected function init()
    {
        parent::init();

        $this->url = dirname($_SERVER['SCRIPT_NAME']);
    }

    /**
     * Get list of twig paths.
     *
     * @return array
     */
    public static function getTwigPaths()
    {
        /** @var UniformResourceLocator $locator */
        $locator = static::gantry()['locator'];

        return $locator->mergeResources(['gantry-theme://twig', 'gantry-engine://twig']);
    }

    /**
     * @see AbstractTheme::setTwigLoaderPaths()
     *
     * @param LoaderInterface $loader
     * @return FilesystemLoader
     */
    protected function setTwigLoaderPaths(LoaderInterface $loader)
    {
        $loader = parent::setTwigLoaderPaths($loader);

        if ($loader) {
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $loader->setPaths(static::getTwigPaths());
            $loader->setPaths($locator->findResources('gantry-pages://'), 'pages');
            $loader->setPaths($locator->findResources('gantry-positions://'), 'positions');
        }

        return $loader;
    }
}
