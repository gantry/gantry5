<?php
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
    /**
     * @return array
     */
    public function getCachePath()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        return $locator->findResource('cache://gantry5', false, true);
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
        return ['' => ['user://']];
    }
}
