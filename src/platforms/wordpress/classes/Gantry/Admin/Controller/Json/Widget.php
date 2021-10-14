<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Admin\JsonController;
use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Platform;

/**
 * Class Widget
 * @package Gantry\Admin\Controller\Json
 */
class Widget extends JsonController
{
    /** @var array */
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
        return new JsonResponse(['html' => $this->render('@gantry-admin/modals/widget-picker.html.twig', $this->params)]);
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

        if (isset($this->params['scope'])) {
            $scope = $this->params['scope'];
            $block = BlueprintForm::instance("{$scope}/block.yaml", 'gantry-admin://blueprints');

            // Load particle blueprints.
            $validator = $this->loadBlueprints($scope);
            $callable = static function () use ($validator) {
                return $validator;
            };
        } else {
            $block = null;
            $callable = null;
        }

        if (!empty($cast)) {
            $instance = $this->castInput($instance);
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
        $item = new Config($data, $callable);
        $item->def('type', 'particle');
        $item->def('title', $widgetType->name);
        $item->def('options.type', $widgetType->id_base);
        $item->def('options.particle', []);
        $item->def('options.block', []);

        $this->params += [
            'item'          => $item,
            'block'         => $block,
            'data'          => $data,
            'form'          => $form,
            'prefix'        => "widget.{$name}.",
            'route'         => "configurations.default.settings",
            'action'        => "widget/{$name}/validate"
        ];

        return new JsonResponse(['html' => $this->render('@gantry-admin/modals/widget.html.twig', $this->params)]);
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

        $block = $this->request->post->getArray('block');
        foreach ($block as $key => $param) {
            if ($param === '') {
                unset($block[$key]);
            }
        }

        // Create configuration from the defaults.
        $data = new Config([
            'type' => 'widget',
            'widget' => $name,
            'title' => $this->request->post['title'] ?: $widgetType->name,
            'options' => [
                'widget' => $instance
            ]
        ]);

        if ($block) {
            $menuitem = [
                'type' => 'particle',
                'particle' => 'widget',
                'title' => $data['title'],
                'options' => [
                    'particle' => [
                        'enabled' => 1,
                        'widget' => $data->toArray(),
                    ],
                    'block' => $block
                ]
            ];

            // Fill parameters to be passed to the template file.
            $this->params['item'] = $menuitem;

            $html = $this->render('@gantry-admin/menu/item.html.twig', $this->params);

            return new JsonResponse(['item' => $menuitem, 'html' => $html]);
        }

       return new JsonResponse(['item' => $data->toArray()]);
    }

    /**
     * @param string $name
     * @return \WP_Widget
     * @throws \RuntimeException
     */
    protected function getWidgetType($name)
    {
        /** @var Platform $platform */
        $platform = $this->container['platform'];

        $widgets = $platform->listWidgets();
        if (!isset($widgets[$name])) {
            throw new \RuntimeException(sprintf("Widget '%s' not found", $name), 404);
        }

        /** @var \WP_Widget $widget */
        $widget = clone $widgets[$name]['widget'];
        $widget->number = 0;

        return $widget;
    }

    /**
     * Load blueprints.
     *
     * @param string $name
     * @return BlueprintForm
     */
    protected function loadBlueprints($name = 'menu')
    {
        return BlueprintForm::instance("menu/{$name}.yaml", 'gantry-admin://blueprints');
    }

    /**
     * @param array $input
     * @return array
     */
    protected function castInput(array $input)
    {
        // TODO: Following code is a hack; we really need to pass the data as JSON instead of individual HTTP fields
        // TODO: in order to avoid casting. Main issue is that "true" could also be valid text string.
        // Convert strings back to native values.
        foreach ($input as $key => $field) {
            if (is_array($field)) {
                $input[$key] = $this->castInput($field);
            } elseif (strtolower($field) === 'true') {
                $input[$key] = true;
            } elseif (strtolower($field) === 'false') {
                $input[$key] = false;
            } elseif ((string) $field === (string)(int) $field) {
                $input[$key] = (int)$field;
            }
        }

        return $input;
    }
}
