<?php
defined('ABSPATH') or die;

add_action( 'admin_enqueue_scripts', 'gantry_admin_scripts' );
add_action( 'admin_print_styles', 'gantry_admin_print_styles', 200 );
add_action( 'admin_print_scripts', 'gantry_admin_print_scripts', 200 );

// Adjust menu to contain Gantry stuff.
add_action(
    'admin_menu',
    function () {
        remove_submenu_page( 'themes.php', 'theme-editor.php' );
        add_theme_page( 'Layout Manager', 'Layout Manager', 'manage_options', 'layout-manager', 'gantry_layout_manager' );
    },
    102
);

function gantry_admin_scripts() {
    if( isset( $_GET['page'] ) && $_GET['page'] == 'layout-manager' ) {
        gantry_layout_manager();
    }
}
function gantry_admin_print_styles() {
    $styles = \Gantry\Framework\Document::$styles;
    if ($styles) {
        echo implode("\n", $styles) . "\n";
    }
}
function gantry_admin_print_scripts() {
    $scripts = \Gantry\Framework\Document::$scripts;
    if ($scripts) {
        echo implode("\n", $scripts) . "\n";
    }
}

function gantry_layout_manager() {
    static $output = null;

    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    if ($output) {
        echo $output;
        return;
    }

    // Define Gantry Admin services.
    $gantry = Gantry\Framework\Gantry::instance();
    $gantry['admin.config'] = function ($c) {
        return \Gantry\Framework\Config::instance(
            GANTRYADMIN_PATH . '/cache/config.php',
            GANTRYADMIN_PATH
        );
    };
    $gantry['admin.template'] = function ( $c ) {
        return new \Gantry\Framework\AdminTheme( GANTRYADMIN_PATH );
    };

    // Boot the service.
    $config = $gantry['admin.config'];
    $theme = $gantry['admin.template'];

    // Render the page.
    $output = $theme->render( 'gantry/overview.html.twig' );
}
