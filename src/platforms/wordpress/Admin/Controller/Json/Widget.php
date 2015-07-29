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

namespace Gantry\Admin\Controller\Json;

use Gantry\Admin\Controller\Html\Settings;
use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Controller\JsonController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use RocketTheme\Toolbox\Blueprints\Blueprints;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Widget extends JsonController
{
    protected $httpVerbs = [
        'GET'    => [
            '/'                  => 'select',
            '/*'                 => 'widget',
        ],
        'POST'   => [
            '/'                  => 'undefined',
            '/*'                 => 'widget',
            '/*/validate'        => 'validate',
        ]
    ];

    /**
     * Return a modal content for selecting a widget.
     *
     * @return JsonResponse
     */
    public function select()
    {
        return new JsonResponse(['html' => $this->container['admin.theme']->render('@gantry-admin/modals/widget-picker.html.twig', $this->params)]);
    }

    /**
     * Return form for the widget (filled with data coming from POST).
     *
     * @param  string $name
     * @return JsonResponse
     * @throws \RuntimeException
     */
    public function widget($name)
    {
        $data = $this->request->post['item'];
        if ($data) {
            $data = json_decode($data, true);
        } else {
            $data = $this->request->post->getArray();
            $cast = true;
        }

        if ($data && (!isset($data['type']) || $data['type'] !== 'widget' || !isset($data['widget']))) {
            throw new \RuntimeException('Bad request data.', 400);
        }

        $instance = isset($data['options']['widget']) ? $data['options']['widget'] : [];

        if (!empty($cast)) {
            // TODO: Following code is a hack; we really need to pass the data as JSON instead of individual HTTP fields
            // TODO: in order to avoid casting. Main issue is that "true" could also be valid text string.
            // Convert strings back to native values.
            foreach ($instance as $key => $field) {
                if (strtolower($field) === 'true') {
                    $instance[$key] = true;
                } elseif (strtolower($field) === 'false') {
                    $instance[$key] = false;
                } elseif ((string) $field === (string)(int) $field) {
                    $instance[$key] = intval($field);
                }
            }
        }

        $widgetType = $this->getWidgetType($name);
        $widgetType->number = 0;
        ob_start();
        // TODO: We might want to add the filters back; for now we just assume that widget works like the_widget().
        //$instance = apply_filters( 'widget_form_callback', $instance, $data );
        if ( false !== $instance ) {
            $return = $widgetType->form($instance);
            //do_action_ref_array( 'in_widget_form', array( &$widgetType, &$return, $instance ) );
        }
        $form = ob_get_clean();

        // Create configuration from the defaults.
        $item = new Config($data);
        $item->def('type', 'particle');
        $item->def('title', $widgetType->name);
        $item->def('options.type', $widgetType->id_base);
        $item->def('options.particle', []);
        $item->def('options.block', []);

        $this->params += [
            'item'          => $item,
            'data'          => $data,
            'form'          => $form,
            'prefix'        => "widget.{$name}.",
            'route'         => "configurations.default.settings",
            'action'        => "widget/{$name}/validate"
        ];

        return new JsonResponse(['html' => $this->container['admin.theme']->render('@gantry-admin/modals/widget.html.twig', $this->params)]);
    }

    /**
     * Validate data for the widget.
     *
     * @param string $name
     * @return JsonResponse
     */
    public function validate($name)
    {
        $widgetType = $this->getWidgetType($name);

        $old_instance = [];
        $new_instance = $this->request->post->getArray("widget-{$widgetType->id_base}.0");

        // Prevent caching.
        $cache_state = \wp_suspend_cache_addition();
        \wp_suspend_cache_addition(true);

        // Update widget by using its own method.
        $instance = $widgetType->update($new_instance, $old_instance);

        // Restore caching.
        \wp_suspend_cache_addition($cache_state);

        // Apply widget filters.
        // TODO: We might want to add the filters back; for now we just assume that widget works like the_widget().
        //$instance = \apply_filters('widget_update_callback', $instance, $new_instance, $old_instance, $widgetType);

        if ($instance === false) {
            throw new \RuntimeException('Filter prevented widget from being saved.', 403);
        }

        // Create configuration from the defaults.
        $data = new Config([
            'type' => 'widget',
            'widget' => $name,
            'title' => $this->request->post['title'] ?: $widgetType->name,
        ]);
        $data->set('options.widget', $instance);
        $data->def('options.enabled', 1);

        return new JsonResponse(['item' => $data->toArray()]);
    }

    /**
     * @param string $name
     * @return \WP_Widget
     * @throws \RuntimeException
     */
    protected function getWidgetType($name)
    {
        $widgets = $this->container['platform']->listWidgets();
        if (!isset($widgets[$name])) {
            throw new \RuntimeException("Widget '{$name} not found", 404);
        }

        /** @var \WP_Widget $widget */
        $widget = clone $widgets[$name]['widget'];
        $widget->number = 0;

        return $widget;
    }
}
