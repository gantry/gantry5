<?php
/**
 * Plugin Name: Gantry 5 Framework
 * Plugin URI: //http://gantry.org/
 * Description: Framework for Gantry 5 based templates.
 * Version: 5.0
 * Author: RocketTheme
 * Author URI: http://www.rockettheme.com/
 * License: GPL2
 */
defined( 'ABSPATH' ) or die;

require_once __DIR__ . '/src/Loader.php';

if ( !is_admin() ) {
    return;
}

if (!defined('GANTRYADMIN_PATH')) {
    // Works also with symlinks.
    define('GANTRYADMIN_PATH', rtrim(WP_PLUGIN_DIR, '/\\') . '/gantry5');
}
