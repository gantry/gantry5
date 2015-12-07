<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin;

use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ThemeList
{
    /**
     * @return array
     */
    public static function getThemes()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $files = Folder::all('gantry-themes://', ['recursive' => false, 'files' => false]);

        /** @var array|ThemeDetails[] $list */
        $list = [];

        natsort($files);

        foreach ($files as $theme) {
            if ($locator('gantry-themes://' . $theme . '/gantry/theme.yaml')) {
                $details = new ThemeDetails($theme);

                // Stream needs to be valid URL.
                $streamName = 'gantry-themes-' . preg_replace('|[^a-z\d+.-]|ui', '-', $theme);
                if (!$locator->schemeExists($streamName)) {
                    $locator->addPath($streamName, '', $details->getPaths());
                }

                $details['name'] = $theme;
                $details['title'] = $details['details.name'];
                $details['preview_url'] = $gantry['platform']->getThemePreviewUrl($theme);
                $details['admin_url'] = $gantry['platform']->getThemeAdminUrl($theme);
                $details['params'] = [];

                $list[$details->name] = $details;

            }
        }

        // Add Thumbnails links after adding all the paths to the locator.
        foreach ($list as $details) {
            $details['thumbnail'] = $details->getUrl("details.images.thumbnail");
        }

        return $list;
    }

    public static function getTheme($name)
    {
        $themes = static::getThemes();
        return isset($themes[$name]) ? $themes[$name] : null;
    }
}
