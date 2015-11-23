<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Theme\AbstractTheme;
use Gantry\Component\Theme\ThemeTrait;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Theme
 * @package Gantry\Framework
 */
class Theme extends AbstractTheme
{
    use ThemeTrait;

        /**
     * @see AbstractTheme::setTwigLoaderPaths()
     *
     * @param \Twig_Loader_Filesystem $loader
     */
    protected function setTwigLoaderPaths(\Twig_Loader_Filesystem $loader)
    {
        $gantry = static::gantry();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $paths = $locator->mergeResources(['gantry-theme://templates', 'gantry-engine://templates']);

        // TODO: right now we are replacing all paths; we need to do better, but there are some issues with this call.
        $loader->setPaths($paths);

        parent::setTwigLoaderPaths($loader);
    }
}
