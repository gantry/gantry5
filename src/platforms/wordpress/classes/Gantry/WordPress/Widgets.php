<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Framework\Theme;

/**
 * Class Widgets
 * @package Gantry\WordPress
 */
abstract class Widgets
{
    use GantryTrait;

    /** @var array */
    static protected $chromeArgs = [];

    /**
     * @param string $key
     * @param array $params
     * @return false|string|null
     */
    public static function displayPosition($key, array $params = [])
    {
        $key = \sanitize_title($key);

        // Do nothing if sidebar is not active.
        if (!\is_active_sidebar($key)) {
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
            $sidebars_widgets = \wp_get_sidebars_widgets();
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
                        [array_merge($sidebar, ['widget_id' => $id, 'widget_name' => $name])],
                        (array)$wp_registered_widgets[$id]['params']
                    );

                    // Apply sidebar filter for rokbox and other plugins.
                    $args = \apply_filters('dynamic_sidebar_params', $args);

                    // Grab the content of the plugin.
                    ob_start();
                    call_user_func_array($callback, $args);
                    $contents = ob_get_clean();

                    // As we already rendered content, we can later just display it.
                    $wp_registered_widgets[$id]['callback'] = function () use ($contents) {
                        echo $contents;
                    };
                }
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

    /**
     * @param array|string $instance
     * @param array $params
     * @return string|null
     */
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

        if (!$gantry5Widget && trim($html)) {
            /** @var Theme $theme */
            $theme = static::gantry()['theme'];
            $theme->wordpress(true);
        }

        return $html;
    }

    /**
     * @param string|int $sidebar_id
     * @param string|int $id
     * @param array $props
     * @return string|null
     */
    public static function getAjax($sidebar_id, $id, array $props = [])
    {
        global $wp_registered_sidebars, $wp_registered_widgets;

        // Do nothing if sidebar is not active or widget doesn't exist.
        if (!$sidebar_id || !$id || empty($wp_registered_widgets[$id]) || !\is_active_sidebar($sidebar_id)) {
            return null;
        }

        // Make sure we have Gantry 5 compatible widget.
        if (empty($wp_registered_widgets[$id]['gantry5'])) {
            return null;
        }

        $sidebar = $wp_registered_sidebars[$sidebar_id];
        $callback = $wp_registered_widgets[$id]['callback'];

        // Pre-render Gantry widget.
        $contents = null;
        if (is_callable($callback)) {
            $name = $wp_registered_widgets[$id]['name'];

            $args = array_merge(
                [array_merge($sidebar, [
                    'widget_id' => $id,
                    'widget_name' => $name,
                    'ajax' => $props,
                    'before_widget' => '',
                    'after_widget' => '',
                    'before_title' => '',
                    'after_title' => '',
                ])],
                (array)$wp_registered_widgets[$id]['params']
            );

            // Apply sidebar filter for rokbox and other plugins.
            $args = \apply_filters('dynamic_sidebar_params', $args);

            // Grab the content of the plugin.
            ob_start();
            call_user_func_array($callback, $args);
            $contents = ob_get_clean();
        }

        return $contents;
    }

    /**
     * @return array
     */
    public static function listWidgets()
    {
        static $list;

        if ($list !== null) {
            return $list;
        }

        $widgets = $GLOBALS['wp_widget_factory']->widgets;

        $list = [];
        foreach ($widgets as $key => $widget) {
            $description = isset($widget->widget_options['description']) ? $widget->widget_options['description'] : '';
            $info = [
                'id' => $widget->id_base, 'title' => $widget->name, 'description' => $description, 'class' => $key,
                'widget' => $widget
            ];
            $list[$widget->id_base] = $info;
        }

        uasort($list, static function ($a, $b) { return strcmp($a['title'], $b['title']); });

        return $list;
    }

    /**
     * @param bool $particlesOnly
     * @return array
     */
    public static function export($particlesOnly = true)
    {
        global $wp_registered_widget_controls;

        // Get all widget instances.
        $list = [];
        foreach ($wp_registered_widget_controls as $widget) {
            if (!empty($widget['id_base'])) {
                $base = $widget['id_base'];
                if ($base === 'particle_widget') {
                    $type = 'gantry.particle';
                    $isParticle = true;
                } else {
                    $type = "wordpress.{$base}";
                    $isParticle = false;
                    if ($particlesOnly) {
                        continue;
                    }
                }
                foreach (\get_option("widget_{$base}") as $id => $instance) {
                    if (is_numeric($id)) {
                        $wordpress = $particle = [];
                        if (!$isParticle) {
                            $wordpress = $instance;
                        } else {
                            if (isset($instance['g5_classes'])) {
                                $wordpress['g5_classes'] = $instance['g5_classes'];
                                unset($instance['g5_classes']);
                            }
                            $particle = $instance;
                        }


                        $data = ['type' => $type];
                        if ($particle) {
                            $data['particle'] = $particle;
                        }
                        if ($wordpress) {
                            $data['wordpress'] = $wordpress;
                        }

                        $list["{$base}-{$id}"] = $data;
                    }
                }
            }
        }

        $sidebars = [];
        foreach (\get_option('sidebars_widgets') as $sidebar => $widgets) {
            if (!is_array($widgets) || $sidebar === 'wp_inactive_widgets' || strpos($sidebar, 'orphaned_widgets_') === 0) {
                continue;
            }

            foreach ($widgets as $widgetId) {
                if (isset($list[$widgetId])) {
                    $sidebars[$sidebar][] = $list[$widgetId];
                }
            }
        }

        ksort($sidebars);

        return $sidebars;
    }

    /**
     * @param array $positions
     */
    public static function import(array $positions)
    {
        // Load sidebars.
        $sidebars = (array)\get_option('sidebars_widgets');
        $widgets = [];

        foreach ($positions as $sidebar => $list) {
            foreach ($list as $data) {
                $type = static::getImportType($data['type']);
                if (!$type) {
                    continue;
                }

                if (!isset($widgets[$type])) {
                    // Load widgets.
                    $widgets[$type] = (array)\get_option("widget_{$type}");
                }

                static::addWidget($type, $data, $sidebar, $widgets[$type], $sidebars);
            }
        }

        // Bulk update all widgets and sidebars.
        foreach ($widgets as $type => $list) {
            \update_option("widget_{$type}", $list);
        }

        \update_option('sidebars_widgets', $sidebars);
    }

    /**
     * @param string $type
     * @param array $data
     * @param string $sidebar
     */
    public static function create($type, array $data, $sidebar = 'wp_inactive_widgets')
    {
        // Load widgets and sidebars.
        $sidebars = (array)\get_option('sidebars_widgets');
        $widgets = (array)\get_option("widget_{$type}");

        // Add widget to the sidebar.
        static::addWidget($type, $data, $sidebar, $widgets, $sidebars);

        // Save widgets and sidebars.
        \update_option("widget_{$type}", $widgets);
        \update_option('sidebars_widgets', $sidebars);
    }

    /**
     * @param bool $next
     * @return int
     */
    protected static function displayWidgetId($next = false)
    {
        static $id = -1;

        if ($next) {
            $id--;
        }

        return $id;
    }

    /**
     * @param string $type
     * @return string|false
     */
    protected static function getImportType($type)
    {
        if ($type === 'gantry.particle') {
            list ($scope, $type) = ['wordpress', 'particle_widget'];
        } else {
            list ($scope, $type) = explode('.', $type, 2);
        }

        if ($scope !== 'wordpress') {
            return false;
        }

        // Check if widget type exists in the site.
        $widgetTypes = static::listWidgets();
        if (!isset($widgetTypes[$type])) {
            return false;
        }

        return $type;
    }

    /**
     * @param string $type
     * @param array $data
     * @param string|int $sidebar
     * @param array $widgets
     * @param array $sidebars
     */
    protected static function addWidget($type, array $data, $sidebar, array &$widgets, array &$sidebars)
    {
        global $wp_registered_sidebars;

        $widget = [];
        if ($type === 'particle_widget') {
            $widget = isset($data['particle']) ? $data['particle'] : [];

        }

        if (isset($data['wordpress'])) {
            $widget += $data['wordpress'];
        }

        // Check if sidebar exists in the site.
        if (!isset($wp_registered_sidebars[$sidebar])) {
            $sidebar = 'wp_inactive_widgets';
        }

        // Add new widget while making sure that key 0 will not be used.
        $widgets[0] = true;
        $widgets[] = $widget;
        end($widgets);
        $id = key($widgets);

        // Append _multiwidget=1 into the end of the list.
        unset($widgets[0], $widgets['_multiwidget']);
        $widgets['_multiwidget'] = 1;

        $sidebars[$sidebar][] = "{$type}-{$id}";
    }

    /**
     * @param array $params
     * @return array
     */
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

    /**
     * @param string|object $id
     * @return string
     */
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

    /**
     * @param string|int $id
     * @return array|null
     */
    protected static function getWidgetData($id)
    {
        $widgets = static::listWidgets();
        if (!isset($widgets[$id])) {
            return null;
        }
        return $widgets[$id];
    }

    /**
     * @param string $chrome
     * @return array
     */
    protected static function getChromeArgs($chrome = 'gantry')
    {
        /** @var Theme $theme */
        $theme = static::gantry()['theme'];

        return (array)$theme->details()->get('chrome.' . $chrome);
    }

    /**
     * @param string $widgetClass
     * @param mixed $chrome
     * @return array
     */
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

    /**
     * @param object $widget
     * @param mixed $return
     * @param array $instance
     * @return null
     */
    public static function widgetCustomClassesForm($widget, $return, $instance)
    {
        $instance = \wp_parse_args($instance, ['g5_classes' => '']);

        // TODO: Move this HTML to a Twig file ?
        ?>
        <p>
            <label for="<?php echo $widget->get_field_id('g5_classes'); ?>"><?php \_e('Custom class(es):', 'gantry5'); ?></label>
            <input type="text" class="widefat" id="<?php echo $widget->get_field_id('g5_classes'); ?>"
                   name="<?php echo $widget->get_field_name('g5_classes'); ?>" value="<?php echo \esc_attr($instance['g5_classes']); ?>"/>
            <br />
            <small><?php \_e('Multiple class names must be separated by white space characters.', 'gantry5'); ?></small>
        </p>
        <?php

        return null;
    }

    /**
     * @param array $instance
     * @param array $new_instance
     * @param array $old_instance
     * @param mixed $widget
     * @return array
     */
    public static function widgetCustomClassesUpdate($instance, $new_instance, $old_instance, $widget)
    {
        if (!empty($new_instance['g5_classes'])) {
            $instance['g5_classes'] = implode(' ', array_map('sanitize_html_class', explode(' ', $new_instance['g5_classes'])));
        } else {
            $instance['g5_classes'] = '';
        }

        return $instance;
    }

    /**
     * @param array $params
     * @return array
     */
    public static function widgetCustomClassesSidebarParams($params)
    {
        global $wp_registered_widgets;

        $widget_id  = $params[0]['widget_id'];
        $widget_obj = $wp_registered_widgets[$widget_id];

        if (!is_array($widget_obj['callback']) || !isset($widget_obj['callback'][0]->option_name)) {
            return $params;
        }

        $widget_options = \get_option($widget_obj['callback'][0]->option_name);

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
