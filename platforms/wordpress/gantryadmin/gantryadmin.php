<?php
/**
 * Plugin Name: Gantry5 Admin
 * Plugin URI: //http://gantry-framework.org/
 * Description: Administrate Gantry5 based templates.
 * Version: 5.0
 * Author: RocketTheme
 * Author URI: http://www.rockettheme.com/
 * License: GPL2
 */
defined('ABSPATH') or die;

use Gantry\Framework\Gantry;

if (!is_admin()) {
    return;
}

if (!defined('GANTRYADMIN_PATH')) {
    define('GANTRYADMIN_PATH', rtrim(WP_PLUGIN_DIR, '/\\') . '/gantryadmin');
}
