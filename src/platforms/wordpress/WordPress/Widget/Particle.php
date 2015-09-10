<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress\Widget;

use Gantry\Admin\Router;
use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;

class Particle extends \WP_Widget
{
    protected $container;

    public function __construct()
    {
        global $pagenow;

        parent::__construct(
            'particle_widget',
            __( 'Gantry 5 Particle', 'gantry5' ),
            ['description' => __( 'Displays Gantry 5 particle instance in a widget position.', 'gantry5' )]
        );

        try {
            $this->container = Gantry::instance();
        } catch (Exception $e) {}

        $ajax = ($pagenow === 'admin-ajax.php' && ( isset( $_POST['action'] ) && $_POST['action'] === 'save-widget' ) );
        if (is_admin() && (in_array($pagenow, ['widgets.php', 'customize.php']) || $ajax)) {
            // Initialize administrator if already not done that.
            if (!isset($this->container['router'])) {
                $this->container['router'] = function ($c) {
                    return new Router($c);
                };

                $this->container['router']->boot()->load();
                $this->container['admin.theme']->render('@gantry-admin/partials/layout.html.twig', ['content' => '']);
            }
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
        /** @var Theme $theme */
        $theme = $this->container['theme'];

        $instance += [
            'type' => 'particle',
            'particle' => 'none',
            'options' =>  ['particle' => []],
        ];

        $context = array(
            'gantry' => $this->container,
            'inContent' => true,
            'segment' => array(
                'type' => $instance['type'],
                'subtype' => $instance['particle'],
                'attributes' =>  $instance['options']['particle'],
            )
        );

        echo $args['before_widget'];
        echo apply_filters('widget_content', $theme->render("@nucleus/content/particle.html.twig", $context));
        echo $args['after_widget'];
    }

    /**
     * Outputs the options form on admin.
     *
     * @param array $instance The widget options
     */
    public function form($instance)
    {
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

        echo "<input id=\"{$fieldId}\" name=\"{$fieldName}\" type=\"hidden\" value=\"" . esc_attr($title) . "\" />";

        $params = [
            'content' => $this->container['admin.theme']->render('@gantry-admin/forms/fields/gantry/particle.html.twig', $field)
        ];

        echo '<p>' . __('Click on the button below to choose a Particle.', 'gantry5') . '</p>';

        echo $this->container['admin.theme']->render('@gantry-admin/partials/inline.html.twig', $params);
    }

    /**
     * Processing widget options on save.
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     */
    public function update($new_instance, $old_instance)
    {
        $instance = isset($new_instance['particle']) ? json_decode($new_instance['particle'], true) : [];

        return $instance;
    }
}
