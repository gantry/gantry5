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

    /**
     * @return array
     */
    public function getThemePaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        return $locator->getPaths('theme');
    }

    public function getMediaPaths()
    {
        return ['' => ['user://gantry5']];
    }

    public function getEnginesPaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        if (is_link($locator('user://gantry5/engines'))) {
            // Development environment.
            return ['' => ["user://gantry5/engines/{$this->name}", 'user://gantry5/engines/common']];
        }
        return ['' => ['user://gantry5/engines']];
    }

    public function getAssetsPaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        if (is_link($locator('user://gantry5/assets'))) {
            // Development environment.
            return ['' => ['gantry-theme://', "user://gantry5/assets/{$this->name}", 'user://gantry5/assets/common']];
        }

        return ['' => ['gantry-theme://', 'user://gantry5/assets']];
    }
}
