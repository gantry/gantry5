<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Framework\Base\Platform as BasePlatform;
use Grav\Common\Grav;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    protected $name = 'grav';

    /**
     * @return array
     */
    public function getCachePath()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        return $locator->findResource('cache://gantry5', true, true);
    }

    /**
     * @return array
     */
    public function getThemesPaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        return $locator->getPaths('themes');
    }

    public function getMediaPaths()
    {
        return ['' => ['gantry-theme://images', 'page://images', 'user://gantry5/media']];
    }

    public function getEnginesPaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        if (is_link($locator('plugin://gantry5/engines'))) {
            // Development environment.
            return ['' => ["plugin://gantry5/engines/{$this->name}", 'plugin://gantry5/engines/common']];
        }
        return ['' => ['plugin://gantry5/engines']];
    }

    public function getAssetsPaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        if (is_link($locator('plugin://gantry5/assets'))) {
            // Development environment.
            return ['' => ['gantry-theme://', "plugin://gantry5/assets/{$this->name}", 'plugin://gantry5/assets/common']];
        }

        return ['' => ['gantry-theme://', 'plugin://gantry5/assets']];
    }

    /**
     * Get preview url for individual theme.
     *
     * @param string $theme
     * @return null
     */
    public function getThemePreviewUrl($theme)
    {
        return null;
    }

    /**
     * Get administrator url for individual theme.
     *
     * @param string $theme
     * @return string|null
     */
    public function getThemeAdminUrl($theme)
    {
        $grav = Grav::instance();
        return $grav['gantry5_plugin']->base . '/' . $theme;
    }

    public function finalize()
    {
        Document::registerAssets();
    }


    public function settings()
    {
        $grav = Grav::instance();
        return $grav['base_url_relative'] . $grav['admin']->base . '/plugins/gantry5';
    }
}
