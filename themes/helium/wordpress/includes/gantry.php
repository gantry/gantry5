<?php
/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die;

use Gantry\Framework\Gantry;

try {
    // Attempt to locate Gantry Framework if it hasn't already been loaded.
    if (!class_exists('Gantry5\\Loader')) {
        throw new LogicException('Gantry 5 Framework not found!');
    }

    Gantry5\Loader::setup();

    // Get Gantry instance.
    $gantry = Gantry::instance();

    // Initialize the template if not done already.
    if (!isset($gantry['theme.name'])) {
        $gantry['theme.path'] = get_stylesheet_directory();
        $gantry['theme.parent'] = get_option('template');
        $gantry['theme.name'] = get_option('stylesheet');
    }

    // Only a single template can be loaded at any time.
    if (!isset($gantry['theme'])) {
        include_once $gantry['theme.path'] . '/includes/theme.php';
    }

} catch (Exception $e) {
    // Oops, something went wrong!
    if (is_admin()) {
        // In admin display an useful error.
        add_action('admin_notices', function () use ($e) {
            echo '<div class="error"><p>Failed to load theme: ' . $e->getMessage() . '</p></div>';
        });
        return;
    }

    add_filter('template_include', function () use ($e) {
        if (is_customize_preview() && !class_exists('Timber')) {
            _e('Timber library plugin not found. ', 'g5_helium');
        }
        _e('Theme cannot be used. For more information, please see the notice in administration.', 'g5_helium');
        die();
    });

    return;
}

// Hook into administration.
if (is_admin()) {
    if (file_exists($gantry['theme.path'] . '/admin/init.php')) {
        define('GANTRYADMIN_PATH', $gantry['theme.path'] . '/admin');
    }

    add_action('init', function () {
        if (defined('GANTRYADMIN_PATH')) {
            require_once GANTRYADMIN_PATH . '/init.php';
        }
    });
}

return $gantry;
