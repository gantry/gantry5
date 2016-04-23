<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

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
        static $styles;

        if ($styles === null) {
            $styles = static::loadStyles();
        }

        $list = [];
        foreach ($styles as $style) {
            $list[$style->name] = $style;
        }

        ksort($list);

        return $list;
    }

    public static function getTheme($name)
    {
        $themes = static::getThemes();
        return isset($themes[$name]) ? $themes[$name] : null;
    }

    /**
     * @param string $template
     * @return array
     */
    public static function getStyles($template = null)
    {
        static $styles;

        if ($styles === null) {
            $styles = static::loadStyles();
        }

        if ($template) {
            $list = [];
            foreach ($styles as $style) {
                if ($style->name === $template) {
                    $list[] = $style;
                }
            }

            return $list;
        }

        return $styles;
    }

    protected static function loadStyles()
    {
        // Load styles
        $db    = \JFactory::getDbo();
        $query = $db
            ->getQuery(true)
            ->select('s.id, e.extension_id, s.template AS name, s.title, s.params')
            ->from('#__template_styles AS s')
            ->where('s.client_id = 0')
            ->where('e.enabled = 1')
            ->where('e.state = 0')
            ->leftJoin('#__extensions AS e ON e.element=s.template AND e.type='
            . $db->quote('template') . ' AND e.client_id=s.client_id');

        $db->setQuery($query);
        $templates = (array) $db->loadObjectList();

        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        /** @var array|ThemeDetails[] $list */
        $list = [];

        foreach ($templates as $template)
        {
            if (file_exists(JPATH_SITE . '/templates/' . $template->name . '/gantry/theme.yaml'))
            {
                $details = new ThemeDetails($template->name);
                $details->addStreams();

                $params = new \JRegistry($template->params);

                $details['id'] = $template->id;
                $details['extension_id'] = $template->extension_id;
                $details['name'] = $template->name;
                $details['title'] = $details['details.name'];
                $details['style'] = $template->title;
                $details['preview_url'] = $gantry['platform']->getThemePreviewUrl($template->id);
                $details['admin_url'] = $gantry['platform']->getThemeAdminUrl($template->name);
                $details['params'] = $params->toArray();

                $list[$template->id] = $details;
            }
        }

        // Add Thumbnails links after adding all the paths to the locator.
        foreach ($list as $details) {
            $details['thumbnail'] = $details->getUrl('details.images.thumbnail');
        }

        return $list;
    }
}
