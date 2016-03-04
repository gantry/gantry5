<?php
defined('ABSPATH') or die;

add_action( 'admin_init', 'gantry5_admin_start_buffer', -10000 );
add_action( 'admin_enqueue_scripts', 'gantry5_admin_scripts' );
add_action( 'wp_ajax_gantry5', 'gantry5_layout_manager' );
add_filter( 'upgrader_package_options', 'gantry5_upgrader_package_options', 10000 );
add_filter( 'upgrader_source_selection', 'gantry5_upgrader_source_selection', 0, 4 );

// Check if Timber is active before displaying sidebar button
if ( class_exists( 'Timber' ) ) {
    // Load Gantry 5 icon styling for the admin sidebar
    add_action( 'admin_enqueue_scripts',
        function () {
            if( is_admin() ) {
                wp_enqueue_style( 'wordpress-admin-icon', Gantry\Framework\Document::url( 'gantry-assets://css/wordpress-admin-icon.css' ) );
            }
        }
    );

    // Adjust menu to contain Gantry stuff.
    add_action(
        'admin_menu',
        function () {
            $gantry = Gantry\Framework\Gantry::instance();
            $theme = $gantry['theme']->details()['details.name'];
            remove_submenu_page( 'themes.php', 'theme-editor.php' );
            add_menu_page( $theme . ' Theme', $theme . ' Theme', 'manage_options', 'layout-manager', 'gantry5_layout_manager' );
        },
        100
    );
}

function gantry5_admin_start_buffer() {
    ob_start();
    ob_implicit_flush(false);
}

function gantry5_admin_scripts() {
    if( isset( $_GET['page'] ) && $_GET['page'] == 'layout-manager' ) {
        gantry5_layout_manager();
    }
}

function gantry5_layout_manager() {
    static $output = null;

    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    add_filter( 'admin_body_class', function () {
        return 'gantry5 gantry5-wordpress';
    } );

    if ( $output ) {
        echo $output;
        return;
    }

    // Detect Gantry Framework or fail gracefully.
    if (!class_exists('Gantry5\Loader')) {
        wp_die( __( 'Gantry 5 Framework not found.' ) );
    }

    // Initialize administrator or fail gracefully.
    try {
        Gantry5\Loader::setup();

        $gantry = Gantry\Framework\Gantry::instance();
        $gantry['router'] = function ($c) {
            return new \Gantry\Admin\Router($c);
        };

        // Dispatch to the controller.
        $output = $gantry['router']->dispatch();

    } catch (Exception $e) {
        throw $e;
//        wp_die( $e->getMessage() );
    }
}

// SimpleXmlElement is a weird class that acts like a boolean, we are going to take advantage from that.
class Gantry5Truthy extends SimpleXmlElement { }

function gantry5_upgrader_package_options($options) {
    if ($options['abort_if_destination_exists'] && !$options['clear_destination']) {
        $options['abort_if_destination_exists'] = new Gantry5Truthy('<bool><true></true></bool>');
        $options['hook_extra']['gantry5_abort'] = $options['abort_if_destination_exists'];
    }

    return $options;
}

function gantry5_upgrader_source_selection($source, $remote_source, $this, $hook_extra) {
    if (isset($hook_extra['gantry5_abort'])) {
        // Allow upgrading Gantry themes from uploader.
        if (file_exists($source . '/gantry/theme.yaml')) {
            unset($hook_extra['gantry5_abort']->true);
        }
    }

    return $source;
}
