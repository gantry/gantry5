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
        return Folder::getRelativePath(WP_CONTENT_DIR . '/cache/gantry5');
    }

    public function getThemesPaths()
    {
        return ['' => Folder::getRelativePath(get_theme_root())];
    }

    public function getMediaPaths()
    {
        return ['' => ['media']];
    }
}
