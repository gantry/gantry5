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
        return Folder::getRelativePath(\Mage::getBaseDir('cache')) . '/gantry5';
    }

    public function getThemesPaths()
    {
        return  [
            '' => [
                'skin/frontend',
                'app/design/frontend',
            ]
        ];
    }

    public function getMediaPaths()
    {
        return ['' => ['media']];
    }
}
