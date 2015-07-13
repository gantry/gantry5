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
    /**
     * @return string
     */
    public function getCachePath()
    {
        return \Mage::getBaseDir('cache') . '/gantry5';
    }

    /**
     * @return array
     */
    public function getThemesPaths()
    {
        return  [
            '' => [
                'skin/frontend',
                'app/design/frontend',
            ]
        ];
    }


    /**
     * @return array
     */
    public function getMediaPaths()
    {
        return ['' => ['media']];
    }
}
