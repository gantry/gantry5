<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
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

/**
 * Class Theme
 * @package Gantry\Framework
 */
class Theme extends AbstractTheme
{
    use ThemeTrait;

    /**
     * @var string
     */
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
     * @see AbstractTheme::setTwigLoaderPaths()
     *
     * @param \Twig_LoaderInterface $loader
     */
    protected function setTwigLoaderPaths(\Twig_LoaderInterface $loader)
    {
        if (!($loader instanceof \Twig_Loader_Filesystem)) {
            return;
        }

        $gantry = static::gantry();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader->setPaths($locator->findResources('gantry-engine://twig'));
        $loader->setPaths($locator->findResources('gantry-pages://'), 'pages');
        $loader->setPaths($locator->findResources('gantry-positions://'), 'positions');

        parent::setTwigLoaderPaths($loader);
    }
}
