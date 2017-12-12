<?php
/**
 * Plugin Name: Gantry 5 Debug Bar
 * Plugin URI: http://gantry.org/
 * Description: Debug Bar for Gantry 5
 * Version: @version@
 * Author: RocketTheme, LLC
 * Author URI: http://rockettheme.com/
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gantry5_debugbar
 * Domain Path: /admin/languages
 */

defined('ABSPATH') or die;

// NOTE: This file needs to be PHP 5.2 compatible.

// Fail safe version check for PHP <5.5.9.
if (version_compare(PHP_VERSION, '5.5.9', '<')) {
    if (is_admin()) {
        add_action('admin_notices', 'gantry5_debugbar_php_version_warning');
    }
    return;
}

require_once dirname(__FILE__) . '/Debugger.php';

function gantry5_debugbar_php_version_warning()
{
    echo '<div class="error"><p>';
    echo sprintf("You are running PHP %s, but Gantry 5 DebugBar needs at least PHP %s to run.", PHP_VERSION, '5.4.0');
    echo '</p></div>';
}