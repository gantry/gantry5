<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
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
    protected static $items;

    /**
     * @return array
     */
    public static function getThemes()
    {
        if (!is_array(static::$items)) {
            static::loadThemes();
        }

        return static::$items;
    }

    public static function getTheme($name)
    {
        if (!is_array(static::$items)) {
            static::loadThemes();
        }

        return isset(static::$items[$name]) ? static::$items[$name] : null;
    }

    protected static function loadThemes()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        /** @var array|ThemeDetails[] $list */
        $list = [];

        $files = Folder::all('gantry-themes://', ['recursive' => false, 'files' => false]);
        natsort($files);

        foreach ($files as $theme) {
            try {
                if ($locator('gantry-themes://' . $theme . '/gantry/theme.yaml')) {
                    $details = new ThemeDetails($theme);
                    $details->addStreams();

                    $details['name'] = $theme;
                    $details['title'] = $details['details.name'];
                    $details['preview_url'] = $gantry['platform']->getThemePreviewUrl($theme);
                    $details['admin_url'] = $gantry['platform']->getThemeAdminUrl($theme);
                    $details['params'] = [];

                    $list[$details->name] = $details;
                }
            } catch (\Exception $e) {
                // Do not add broken themes into the list.
                continue;
            }
        }

        // Add Thumbnails links after adding all the paths to the locator.
        foreach ($list as $details) {
            $details['thumbnail'] = $details->getUrl("details.images.thumbnail");
        }

        static::$items = $list;
    }
}
