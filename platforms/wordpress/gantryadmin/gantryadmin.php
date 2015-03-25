<?php
/**
 * Plugin Name: Gantry 5 Admin
 * Plugin URI: //http://gantry-framework.org/
 * Description: Administrate Gantry 5 based templates.
 * Version: 5.0
 * Author: RocketTheme
 * Author URI: http://www.rockettheme.com/
 * License: GPL2
 */
defined( 'ABSPATH' ) or die;

if ( !is_admin() ) {
    return;
}

if (!defined('GANTRYADMIN_PATH')) {
    define('GANTRYADMIN_PATH', rtrim(WP_PLUGIN_DIR, '/\\') . '/gantryadmin');
}
