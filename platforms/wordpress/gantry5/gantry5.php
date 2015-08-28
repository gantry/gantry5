<?php
/**
 * Plugin Name: Gantry 5 Framework
 * Plugin URI: http://gantry.org/
 * Description: Framework for Gantry 5 based templates.
 * Version: @version@
 * Author: RocketTheme, LLC
 * Author URI: http://rockettheme.com/
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gantry5
 * Domain Path: /admin/languages
 */

defined( 'ABSPATH' ) or die;

// NOTE: This file needs to be PHP 5.2 compatible.

require_once dirname(__FILE__) . '/src/Loader.php';

if ( !defined( 'GANTRY5_PATH' ) ) {
    // Works also with symlinks.
    define( 'GANTRY5_PATH', rtrim( WP_PLUGIN_DIR, '/\\' ) . '/gantry5' );
}

if ( !is_admin() ) {
    return;
}

if ( !defined( 'GANTRYADMIN_PATH' ) ) {
    // Works also with symlinks.
    define( 'GANTRYADMIN_PATH', GANTRY5_PATH . '/admin' );
}

// Add Gantry 5 defaults on plugin activation
// TODO: change the admin_init to a better hook ie. only when plugin updates
register_activation_hook( __FILE__, 'gantry5_plugin_defaults' );
add_action( 'admin_init', 'gantry5_plugin_defaults' );

function gantry5_plugin_defaults() {
    $defaults = array(
        'production'       => '1',
        'debug'            => '0',
        'offline'          => '0',
        'offline_message'  => 'Site is currently in offline mode. Please try again later.',
    );

    $option = (array) get_option( 'gantry5_plugin' );

    update_option( 'gantry5_plugin', $option + $defaults );
}

// Initialize plugin language and fallback to en_US if the .mo file can't be found
$domain = 'gantry5';
$languages_path = basename( GANTRY5_PATH ) . '/admin/languages';

if( load_plugin_textdomain( $domain, false, $languages_path ) === false ) {
    add_filter( 'plugin_locale', 'modify_gantry5_locale', 10, 2 );
}

load_plugin_textdomain( $domain, false, $languages_path );

function modify_gantry5_locale( $locale, $domain ) {
    // Revert the gantry5 domain locale to en_US
    if( isset( $domain ) && $domain == 'gantry5' ) {
        $locale = 'en_US';
    }

    return $locale;
}
