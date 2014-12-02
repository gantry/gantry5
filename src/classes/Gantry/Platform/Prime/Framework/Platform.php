<?php
namespace Gantry\Framework;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Platform as BasePlatform;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    public function getCachePath()
    {
        return 'cache';
    }

    public function getThemesPaths()
    {
        return  ['' => ['themes']];
    }

    public function getMediaPaths()
    {
        return ['' => ['media']];
    }

    public function getModules($position)
    {
        $path = PRIME_ROOT . '/positions/' . $position;

        if (!is_dir($path)) {
            return [];
        }

        $params = [
            'levels' => 0,
            'pattern' => '|\.html\.twig|',
            'filters' => ['value' => '|\.html\.twig|']
        ];

        return Folder::all($path, $params);
    }
}
