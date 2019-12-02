<?php
defined('ABSPATH') or die;

use Gantry\Admin\Router;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Theme;
use Gantry5\Loader;

add_action('admin_init', 'gantry5_admin_start_buffer', -10000);
add_action('admin_enqueue_scripts', 'gantry5_admin_scripts');
add_action('wp_ajax_gantry5', 'gantry5_layout_manager');
add_filter('upgrader_package_options', 'gantry5_upgrader_package_options', 10000);
add_filter('upgrader_source_selection', 'gantry5_upgrader_source_selection', 0, 4);
add_action('upgrader_post_install', 'gantry5_upgrader_post_install', 10, 3);


// Check if Timber is active before displaying sidebar button
if (class_exists( 'Timber')) {
    // Load Gantry 5 icon styling for the admin sidebar
    add_action( 'admin_enqueue_scripts',
        static function() {
            if(is_admin()) {
                wp_enqueue_style( 'wordpress-admin-icon', Document::url('gantry-assets://css/wordpress-admin-icon.css'));
            }
        }
    );

    // Adjust menu to contain Gantry stuff.
    add_action(
        'admin_menu',
        static function() {
            $gantry = Gantry::instance();

            /** @var Theme $theme */
            $theme = $gantry['theme'];
            $name = $theme->details()['details.name'];
            remove_submenu_page('themes.php', 'theme-editor.php');
            add_menu_page("{$name} Theme", "{$name} Theme", 'manage_options', 'layout-manager', 'gantry5_layout_manager');
        },
        100
    );
}

function gantry5_admin_start_buffer()
{
    ob_start();
    ob_implicit_flush(false);
}

function gantry5_admin_scripts()
{
    if (isset($_GET['page']) && $_GET['page'] === 'layout-manager') {
        gantry5_layout_manager();
    }
}

function gantry5_layout_manager()
{
    static $output = null;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    add_filter('admin_body_class', static function() {
        return 'gantry5 gantry5-wordpress';
    });

    if ($output) {
        echo $output;
        return;
    }

    // Detect Gantry Framework or fail gracefully.
    if (!class_exists('Gantry5\Loader')) {
        wp_die(__('Gantry 5 Framework not found.'));
    }

    // Initialize administrator or fail gracefully.
    try {
        Loader::setup();

        $gantry = Gantry::instance();
        $router = new Router($gantry);
        $gantry['router'] = $router;

        // Dispatch to the controller.
        $output = $router->dispatch();
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * SimpleXmlElement is a weird class that acts like a boolean, we are going to take advantage from that.
 */
class Gantry5Truthy extends SimpleXMLElement {}

function gantry5_upgrader_package_options($options)
{
    if (isset($options['hook_extra']['type']) && !$options['clear_destination']) {
        if ($options['hook_extra']['type'] === 'theme' && $options['abort_if_destination_exists']) {
            // Prepare for manual theme upgrade.
            $options['abort_if_destination_exists'] = new Gantry5Truthy('<bool><true></true></bool>');
            $options['hook_extra']['gantry5_abort'] = $options['abort_if_destination_exists'];
        } elseif ($options['hook_extra']['type'] === 'plugin' && strpos(basename($options['package']), 'gantry5') !== false) {
            // Allow Gantry plugin to be manually upgraded / downgraded.
            $options['clear_destination'] = true;
        }
    }

    return $options;
}

function gantry5_upgrader_source_selection($source, $remote_source, $upgrader, $options = [])
{
    // Allow upgrading Gantry themes from uploader.
    if (isset($options['gantry5_abort']) && file_exists("{$source}/gantry/theme.yaml")) {
        $upgrader->skin->feedback('Gantry 5 theme detected.');
        unset($options['gantry5_abort']->true);
    }

    return $source;
}

function gantry5_upgrader_post_install($success, $options, $result)
{
    if ($success) {
        $theme = isset($options['gantry5_abort']) && !$options['gantry5_abort'];
        $plugin = (isset($options['plugin']) && $options['plugin'] === 'gantry5/gantry5.php')
            || (isset($options['type']) && $options['type'] === 'plugin' && basename($result['destination']) === 'gantry5');

        // Clear gantry cache after plugin / Gantry theme installs.
        if ($theme || $plugin) {
            global $wp_filesystem;

            $gantry = Gantry::instance();

            /** @var Platform $platform */
            $platform = $gantry['platform'];

            $path = $platform->getCachePath();
            if ($wp_filesystem->is_dir($path)) {
                $wp_filesystem->rmdir($path, true);
            }

            // Make sure that PHP has the latest data of the files.
            clearstatcache();

            // Remove all compiled files from opcode cache.
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        }
    }
}

