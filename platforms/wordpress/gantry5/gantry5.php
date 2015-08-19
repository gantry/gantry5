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
 */
defined( 'ABSPATH' ) or die;

// NOTE: This file needs to be PHP 5.2 compatible.

require_once dirname(__FILE__) . '/src/Loader.php';

if (!defined('GANTRY5_PATH')) {
    // Works also with symlinks.
    define('GANTRY5_PATH', rtrim(WP_PLUGIN_DIR, '/\\') . '/gantry5');
}

if ( !is_admin() ) {
    return;
}

if (!defined('GANTRYADMIN_PATH')) {
    // Works also with symlinks.
    define('GANTRYADMIN_PATH', GANTRY5_PATH . '/admin');
}

// Add Gantry 5 defaults on plugin activation
register_activation_hook( __FILE__, 'gantry5_plugin_defaults' );

function gantry5_plugin_defaults() {
    $defaults = array(
        'production' => '1',
        'debug' => '0',
    );

    add_option( 'gantry5_plugin', $defaults );
}

// Initialize plugin language.
$domain = 'gantry5';
$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

load_textdomain($domain, WP_LANG_DIR . '/gantry5/' . $domain . '-' . $locale . '.mo');
load_plugin_textdomain($domain, false, basename(GANTRY5_PATH) . '/admin/languages');
