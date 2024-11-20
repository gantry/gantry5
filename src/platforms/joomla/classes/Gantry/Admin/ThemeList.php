<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * @package Gantry\Admin
 */
class ThemeList
{
    /** @var ThemeDetails[] */
    protected static $items;

    /** @var array */
    protected static $styles;

    /**
     * @return array
     */
    public static function getThemes(): array
    {
        if (!\is_array(static::$items)) {
            static::loadThemes();
        }

        $list = [];

        foreach (static::$items as $item) {
            $details = static::getTheme($item['name']);

            if ($details) {
                $list[$item['name']] = $details;
            }
        }

        return $list;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public static function getTheme($name)
    {
        $styles = static::getStyles($name);

        return reset($styles);
    }

    /**
     * @param ?string $template
     * @param ?bool $force
     * @return array
     */
    public static function getStyles($template = null, $force = false)
    {
        if ($force || !\is_array(static::$styles)) {
            static::loadStyles();
        }

        if ($template) {
            return isset(static::$styles[$template]) ? static::$styles[$template] : [];
        }

        $list = [];

        foreach (static::$styles as $styles) {
            $list += $styles;
        }

        \ksort($list);

        return $list;
    }

    /**
     * @return void
     */
    protected static function loadThemes(): void
    {
        $gantry = Gantry::instance();

        /** @var Platform $platform */
        $platform = $gantry['platform'];

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        /** @var ThemeDetails[] $list */
        $list = [];

        $files = Folder::all('gantry-themes://', ['recursive' => false, 'files' => false]);
        \natsort($files);

        foreach ($files as $theme) {
            if ($locator('gantry-themes://' . $theme . '/gantry/theme.yaml')) {
                $details = new ThemeDetails($theme);
                $details->addStreams();

                $details['name']        = $theme;
                $details['title']       = $details['details.name'];
                $details['preview_url'] = null;
                $details['admin_url']   = $platform->getThemeAdminUrl($theme);
                $details['params']      = [];

                $list[$details->name] = $details;
            }
        }

        // Add Thumbnails links after adding all the paths to the locator.
        foreach ($list as $details) {
            $details['thumbnail'] = $details->getUrl('details.images.thumbnail');
        }

        static::$items = $list;
    }

    /**
     * @return void
     */
    protected static function loadStyles()
    {
        $gantry = Gantry::instance();

        /** @var Platform $platform */
        $platform = $gantry['platform'];

        /** @var DatabaseInterface $db */
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();

        $query->select(
            [
                $db->quoteName('s.id'),
                $db->quoteName('s.template', 'name'),
                $db->quoteName('s.title'),
                $db->quoteName('s.params'),
                $db->quoteName('e.extension_id'),

            ]
        )
            ->from($db->quoteName('#__template_styles', 's'))
            ->where(
                [
                    $db->quoteName('s.client_id') . ' = 0',
                    $db->quoteName('e.enabled') . ' = 1',
                    $db->quoteName('e.state') . ' = 0',
                ]
            )
            ->leftJoin(
                $db->quoteName('#__extensions', 'e'),
                $db->quoteName('e.element') . ' = ' . $db->quoteName('s.template')
                . ' AND ' . $db->quoteName('e.type') . ' = ' . $db->quote('template')
                . ' AND ' . $db->quoteName('e.client_id') . ' = ' . $db->quoteName('s.client_id')
            )
            ->order('s.id');

        $styles = (array) $db->setQuery($query)->loadObjectList();

        if (!\is_array(static::$items)) {
            static::loadThemes();
        }

        /** @var ThemeDetails[] $list */
        $list = [];

        foreach ($styles as $style) {
            $details = static::$items[$style->name] ?? null;

            if (!$details) {
                continue;
            }

            $params = new Registry($style->params);

            $details = clone $details;
            $details['id']           = $style->id;
            $details['extension_id'] = $style->extension_id;
            $details['style']        = $style->title;
            $details['preview_url']  = $platform->getThemePreviewUrl($style->id);
            $details['params']       = $params->toArray();

            $list[$style->name][$style->id] = $details;
        }

        static::$styles = $list;
    }
}
