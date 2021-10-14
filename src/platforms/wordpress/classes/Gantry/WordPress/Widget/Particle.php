<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress\Widget;

use Gantry\Admin\Router;
use Gantry\Component\Config\Config;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Gantry\Admin\Theme as AdminTheme;

/**
 * Class Particle
 * @package Gantry\WordPress\Widget
 */
class Particle extends \WP_Widget
{
    /** @var bool */
    public $gantry5 = true;

    /** @var Gantry */
    protected $container;
    /** @var array */
    protected $content = [];

    public function __construct()
    {
        global $pagenow;

        parent::__construct(
            'particle_widget',
            __( 'Gantry 5 Particle', 'gantry5' ),
            [
                'description' => __( 'Displays Gantry 5 particle instance in a widget block.', 'gantry5' ),
                'gantry5' => true
            ]
        );

        try {
            $this->container = Gantry::instance();
        } catch (\Exception $e) {}

        $ajax = $pagenow === 'admin-ajax.php' && isset($_POST['action']) && $_POST['action'] === 'save-widget';
        if (\is_admin() && (in_array($pagenow, ['widgets.php', 'customize.php']) || $ajax)) {
            // Initialize administrator if already not done that.
            $this->initialiseGantry();
        }
    }

    /**
     * Initialise Gantry
     */
    public function initialiseGantry()
    {
        if (!defined('GANTRYADMIN_PATH')) {
            // Works also with symlinks.
            define('GANTRYADMIN_PATH', GANTRY5_PATH . '/admin');
        }
        if (!isset($this->container['router'])) {
            $router = new Router($this->container);
            $router->boot()->load();

            $this->container['router'] = $router;

            /** @var AdminTheme $theme */
            $theme = $this->container['admin.theme'];
            $theme->render('@gantry-admin/partials/layout.html.twig', ['content' => '']);
        }
    }

    /**
     * Outputs the content of the widget.
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
        if (!is_array($instance)) {
            $instance = [];
        }

        $sidebar = isset($args['id']) ? (string)$args['id'] : '';
        $widget_id = isset($args['widget_id']) ? preg_replace('/\D/', '', $args['widget_id']) : null;
        $md5 = md5(json_encode($instance));
        $id = isset($instance['id']) ? $instance['id'] : ($widget_id ?: "widget-{$md5}");

        if (!isset($this->content[$md5])) {
            /** @var Theme $theme */
            $theme = $this->container['theme'];

            $instance += [
                'type' => 'particle',
                'particle' => 'undefined',
                'options' =>  ['particle' => []],
            ];

            $type = $instance['type'];
            $particle = $instance['particle'];

            if ($this->container->debug()) {
                /** @var Config $config */
                $config = $this->container['config'];
                $enabled_outline = $config->get("particles.{$particle}.enabled", true);
                $enabled = isset($instance['options']['particle']['enabled']) ? $instance['options']['particle']['enabled'] : true;
                $location = (!$enabled_outline ? 'Outline' : (!$enabled ? 'Widget' : null));

                if ($location) {
                    echo $args['before_widget'];
                    echo '<div class="alert alert-error">The Particle has been disabled from the ' . $location . ' and won\'t render.</div>';
                    echo $args['after_widget'];
                    return;
                }
            }

            $object = (object) [
                'id' => "{$sidebar}-widget-{$particle}-{$id}",
                'type' => $type,
                'subtype' => $particle,
                'attributes' => $instance['options']['particle'],
            ];

            $context = [
                'gantry' => $this->container,
                'inContent' => true
            ];

            if (isset($args['ajax'])) {
                $context['ajax'] = $args['ajax'];
            }

            $this->content[$md5] = $theme->getContent($object, $context);
        }

        $content = $this->content[$md5];

        /** @var Document $document */
        $document = $this->container['document'];
        $document->addBlock($content);

        $html = \apply_filters('widget_content', $content->toString());

        if (trim($html)) {
            echo $args['before_widget'];
            echo $html;
            echo $args['after_widget'];
        }
    }

    /**
     * Outputs the options form on admin.
     *
     * @param array $instance The widget options
     */
    public function form($instance)
    {
        $this->initialiseGantry();

        $field = [
            'layout' => 'input',
            'scope' => '',
            'name' => $this->get_field_name('particle'),
            'field' => [
                'type' => 'gantry.particle',
                'class' => 'input-small',
                'picker_label' => __('Pick a Particle', 'gantry5'),
                'overridable' => false
            ],
            'value' => is_array($instance) ? $instance : []
        ];

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $fieldId = $this->get_field_id('title');
        $fieldName = $this->get_field_name('title');

        echo "<input id=\"{$fieldId}\" name=\"{$fieldName}\" type=\"hidden\" value=\"" . \esc_attr($title) . '" />';


        /** @var AdminTheme $theme */
        $theme = $this->container['admin.theme'];

        $params = [
            'content' => $theme->render('@gantry-admin/forms/fields/gantry/particle.html.twig', $field)
        ];

        echo '<p>' . \__('Clic§k on the button below to choose a Particle.', 'gantry5') . '</p>';

        echo $theme->render('@gantry-admin/partials/inline.html.twig', $params);
    }

    /**
     * Processing widget options on save.
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
        $instance = isset($new_instance['particle']) ? json_decode($new_instance['particle'], true) : [];
        if ($instance == null) {
            $instance = $new_instance;
        }
        return $instance;
    }
}
