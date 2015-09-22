<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Framework\Theme;

abstract class Widgets
{
    use GantryTrait;

    static protected $chromeArgs = [];

    public static function displayPosition($key, array $params = [])
    {
        // Do nothing if sidebar is not active.
        if (!is_active_sidebar($key)) {
            return null;
        }

        // Only pre-render Gantry widgets on prepare layout.
        if (!empty($params['prepare_layout'])) {
            global $wp_registered_sidebars, $wp_registered_widgets;

            $sidebar = $wp_registered_sidebars[$key];
            $sidebars_widgets = wp_get_sidebars_widgets();
            $widgets = !empty($sidebars_widgets[$key]) ? $sidebars_widgets[$key] : [];

            foreach ($widgets as $id) {
                // Make sure we have Gantry 5 compatible widget.
                if (empty($wp_registered_widgets[$id]['gantry5'])) {
                    continue;
                }

                $callback = $wp_registered_widgets[$id]['callback'];

                // Pre-render Gantry widget.
                if (is_callable($callback)) {
                    $name = $wp_registered_widgets[$id]['name'];

                    $args = array_merge(
                        [array_merge($sidebar, array('widget_id' => $id, 'widget_name' => $name, 'muted' => true))],
                        (array) $wp_registered_widgets[$id]['params']
                    );

                    $args = apply_filters('dynamic_sidebar_params', $args);

                    call_user_func_array($callback, $args);
                }
            }

            return '@@DEFERRED@@';
        }

        static::$chromeArgs = static::getChromeArgs($params['chrome']);

        \add_filter('dynamic_sidebar_params', ['Gantry\Wordpress\Widgets', 'sidebarChromeFilter'], -1000);

        ob_start();
        \dynamic_sidebar($key);
        $html = ob_get_clean();

        \remove_filter('dynamic_sidebar_params', ['Gantry\Wordpress\Widgets', 'sidebarChromeFilter'], -1000);

        static::$chromeArgs = [];

        if (trim($html)) {
            /** @var Theme $theme */
            $theme = static::gantry()['theme'];
            $theme->wordpress(true);
        }

        return $html;
    }

    protected static function displayWidgetId($next = false)
    {
        static $id = -1;

        if ($next) {
            $id--;
        }

        return $id;
    }

    public static function displayWidget($instance = [], array $params = [])
    {
        if (is_string($instance)) {
            $instance = json_decode($instance, true);
        }
        if (!isset($instance['type']) || $instance['type'] !== 'widget' || !isset($instance['widget']) || !isset($instance['options'])) {
            return null;
        }

        $options = $instance['options'];

        $widgetData = static::getWidgetData($instance['widget']);
        if (!$widgetData) {
            return '';
        }
        $widgetClass = $widgetData['class'];
        $widgetObj = $widgetData['widget'];
        $gantry5Widget = !empty($widgetObj->gantry5);

        // Do not do anything yet if we are only preparing layout and widget isn't Gantry 5 compatible.
        if (!$gantry5Widget && !empty($params['prepare_layout'])) {
            return '@@DEFERRED@@';
        }

        $id = static::displayWidgetId(true);

        $args = static::getWidgetChrome($widgetClass, $params['chrome']);

        ob_start();
        \the_widget($widgetClass, $options['widget'], $args);
        $html = ob_get_clean();

        if (trim($html) && !$gantry5Widget) {
            /** @var Theme $theme */
            $theme = static::gantry()['theme'];
            $theme->wordpress(true);
        }

        return $html;
    }

    public static function listWidgets()
    {
        $widgets = $GLOBALS['wp_widget_factory']->widgets;

        $list = [];
        foreach ($widgets as $key => $widget) {
            $info = ['id' => $widget->id_base, 'title' => $widget->name, 'description' => $widget->widget_options['description'], 'class' => $key, 'widget' => $widget];
            $list[$widget->id_base] = $info;
        }

        uasort($list, function ($a, $b) { return strcmp($a['title'], $b['title']); });

        return $list;
    }

    public static function sidebarChromeFilter($params)
    {
        if (empty(static::$chromeArgs)) {
            return $params;
        }

        $sidebar = &$params[0];
        $id = $sidebar['widget_id'];

        $sidebar = array_replace($sidebar, static::$chromeArgs);
        $sidebar['before_widget'] = sprintf($sidebar['before_widget'], $id, static::getWidgetClassname($id));

        return $params;
    }

    protected static function getWidgetClassname($id)
    {
        if (is_string($id)) {
            $classes = !empty($GLOBALS['wp_registered_widgets'][$id]) ? $GLOBALS['wp_registered_widgets'][$id]['classname'] : null;
        } else {
            $classes = is_object($id) && !empty($id->widget_options) ? $id->widget_options['classname'] : null;
        }

        // Substitute HTML id and class attributes into before_widget.
        $classname = '';
        foreach ( (array) $classes as $cn ) {
            if ( is_string($cn) )
                $classname .= '_' . $cn;
            elseif ( is_object($cn) )
                $classname .= '_' . get_class($cn);
        }

        return ltrim($classname, '_');
    }

    protected static function getWidgetData($id)
    {
        $widgets = static::listWidgets();
        if (!isset($widgets[$id])) {
            return null;
        }
        return $widgets[$id];
    }

    protected static function getChromeArgs($chrome = 'gantry')
    {
        /** @var Theme $theme */
        $theme = static::gantry()['theme'];
        return (array) $theme->details()->get('chrome.' . $chrome);
    }

    protected static function getWidgetChrome($widgetClass, $chrome)
    {
        global $wp_widget_factory;

        $widgetObj = clone $wp_widget_factory->widgets[$widgetClass];
        $widgetObj->number = static::displayWidgetId();
        $widgetObj->id = "{$widgetObj->id_base}-{$widgetObj->number}";

        $chromeArgs = static::getChromeArgs($chrome);

        if (!empty($chromeArgs['before_widget'])) {
            $chromeArgs['before_widget'] = sprintf(
                $chromeArgs['before_widget'],
                $widgetObj->id,
                static::getWidgetClassname($widgetObj)
            );
        }

        $args = [];

        $search = [
            '%id%',
            '%classname%'
        ];

        $replace = [
            $widgetObj->id,
            $widgetObj->widget_options['classname']
        ];

        foreach($chromeArgs as $key => $arg) {
            $arg = str_replace($search, $replace, $arg);
            $args[$key] = $arg;
        }

        return $args + ['widget_id' => $widgetObj->id, 'widget_name' => $widgetObj->name];
    }
}
