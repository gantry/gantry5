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
        $key = sanitize_title($key);

        // Do nothing if sidebar is not active.
        if (!is_active_sidebar($key)) {
            return null;
        }

        // Set chrome for the filter we add.
        static::$chromeArgs = static::getChromeArgs(isset($params['chrome']) ? $params['chrome'] : null);

        // Add sidebar params filter to give more options.
        \add_filter('dynamic_sidebar_params', ['Gantry\Wordpress\Widgets', 'sidebarChromeFilter'], -1000);

        if (!empty($params['prepare_layout'])) {
            // Only pre-render Gantry widgets on prepare layout.
            global $wp_registered_sidebars, $wp_registered_widgets;

            $sidebar          = $wp_registered_sidebars[$key];
            $sidebars_widgets = wp_get_sidebars_widgets();
            $widgets          = !empty($sidebars_widgets[$key]) ? $sidebars_widgets[$key] : [];

            foreach ($widgets as $id) {
                // TODO: This should display an error
                if (empty($wp_registered_widgets[$id])) {
                    continue;
                }

                // Make sure we have Gantry 5 compatible widget.
                if (empty($wp_registered_widgets[$id]['gantry5'])
                    && $wp_registered_widgets[$id]['classname'] !== 'roksprocket_options'
                    && $wp_registered_widgets[$id]['classname'] !== 'rokgallery_options'
                ) {
                    continue;
                }

                $callback = $wp_registered_widgets[$id]['callback'];

                // Pre-render Gantry widget.
                if (is_callable($callback)) {
                    $name = $wp_registered_widgets[$id]['name'];

                    $args = array_merge(
                        [array_merge($sidebar, array('widget_id' => $id, 'widget_name' => $name))],
                        (array)$wp_registered_widgets[$id]['params']
                    );

                    // Apply sidebar filter for rokbox and other plugins.
                    $args = apply_filters('dynamic_sidebar_params', $args);

                    // Grab the content of the plugin.
                    ob_start();
                    call_user_func_array($callback, $args);
                    $contents = ob_get_clean();

                    // As we already rendered content, we can later just display it.
                    $wp_registered_widgets[$id]['callback'] = function () use ($contents) {
                        echo $contents;
                    };                }
            }

            $html = '@@DEFERRED@@';

        } else {
            // Display whole sidebar.
            ob_start();
            \dynamic_sidebar($key);
            $html = ob_get_clean();
        }

        // Remove sidebar params filter.
        \remove_filter('dynamic_sidebar_params', ['Gantry\Wordpress\Widgets', 'sidebarChromeFilter'], -1000);

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
        $widgetClass   = $widgetData['class'];
        $widgetObj     = $widgetData['widget'];
        $gantry5Widget = !empty($widgetObj->gantry5);

        // Do not do anything yet if we are only preparing layout and widget isn't Gantry 5 compatible.
        if (!$gantry5Widget && !empty($params['prepare_layout'])
            && $widgetData['id'] !== 'roksprocket_options'
            && $widgetData['id'] !== 'rokgallery_options'
        ) {
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
            $info                   =
                ['id'     => $widget->id_base, 'title' => $widget->name, 'description' => $widget->widget_options['description'], 'class' => $key,
                 'widget' => $widget];
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
        $id      = $sidebar['widget_id'];

        $sidebar                  = array_replace($sidebar, static::$chromeArgs);
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
        foreach ((array)$classes as $cn) {
            if (is_string($cn)) {
                $classname .= '_' . $cn;
            } elseif (is_object($cn)) {
                $classname .= '_' . get_class($cn);
            }
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
        return (array)$theme->details()->get('chrome.' . $chrome);
    }

    protected static function getWidgetChrome($widgetClass, $chrome)
    {
        global $wp_widget_factory;

        $widgetObj         = clone $wp_widget_factory->widgets[$widgetClass];
        $widgetObj->number = static::displayWidgetId();
        $widgetObj->id     = "{$widgetObj->id_base}-{$widgetObj->number}";

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

        foreach ($chromeArgs as $key => $arg) {
            $arg        = str_replace($search, $replace, $arg);
            $args[$key] = $arg;
        }

        return $args + ['widget_id' => $widgetObj->id, 'widget_name' => $widgetObj->name];
    }

    public static function widgetCustomClassesForm($widget, $return, $instance)
    {
        $instance = wp_parse_args($instance, ['g5_classes' => '']);

        // TODO: Move this HTML to a Twig file ?
        ?>
        <p>
            <label for="<?php echo $widget->get_field_id('g5_classes'); ?>"><?php _e('Custom class(es):', 'gantry5'); ?></label>
            <input type="text" class="widefat" id="<?php echo $widget->get_field_id('g5_classes'); ?>"
                   name="<?php echo $widget->get_field_name('g5_classes'); ?>" value="<?php echo esc_attr($instance['g5_classes']); ?>"/>
            <br />
            <small><?php _e('Multiple class names must be separated by white space characters.', 'gantry5'); ?></small>
        </p>
        <?php

        return null;
    }

    public static function widgetCustomClassesUpdate($instance, $new_instance, $old_instance, $widget)
    {
        if (!empty($new_instance['g5_classes'])) {
            $instance['g5_classes'] = implode(' ', array_map('sanitize_html_class', explode(' ', $new_instance['g5_classes'])));
        } else {
            $instance['g5_classes'] = '';
        }

        return $instance;
    }

    public static function widgetCustomClassesSidebarParams($params)
    {
        global $wp_registered_widgets;
        $widget_id  = $params[0]['widget_id'];
        $widget_obj = $wp_registered_widgets[$widget_id];

        if (!is_array($widget_obj['callback']) || !isset($widget_obj['callback'][0]->option_name)) {
            return $params;
        }

        $widget_options = get_option($widget_obj['callback'][0]->option_name);

        if (empty($widget_options)) {
            return $params;
        }

        $widget_num = $widget_obj['params'][0]['number'];

        if (empty($widget_options[$widget_num])) {
            return $params;
        }

        $instance = $widget_options[$widget_num];

        if (!empty($instance['g5_classes'])) {
            $params[0]['before_widget'] = preg_replace('/class="/', sprintf('class="%s ', $instance['g5_classes']), $params[0]['before_widget'], 1);
        }

        return $params;
    }
}
