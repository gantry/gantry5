<?php
// phpcs:disable PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
/**
 * Plugin Name: Gantry 5 Framework
 * Plugin URI: http://gantry.org/
 * Description: Framework for Gantry 5 based themes.
 * Version: @version@
 * Author: Tiger12, LLC
 * Author URI: http://tiger12.com/
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gantry5
 * Domain Path: /admin/languages
 *
 * originalCreator: RocketTheme (Gantry Framework)
 * currentDeveloper: Tiger12, LLC
 */

defined('ABSPATH') or die;

// Fail safe version check for PHP < 8.1.0
if (PHP_VERSION_ID < 80100) {
    if (is_admin()) {
        add_action('admin_notices', 'gantry5_php_version_error');
    }
    return;
}

require_once dirname(__FILE__) . '/src/Loader.php';

if (!defined('GANTRY5_PATH')) {
    // Works also with symlinks.
    define('GANTRY5_PATH', rtrim(WP_PLUGIN_DIR, '/\\') . '/gantry5');
}

if (!is_admin()) {
    return;
}

// Load plugin settings.
require_once dirname(__FILE__) . '/admin/settings.php';

if (!defined('GANTRYADMIN_PATH')) {
    // Works also with symlinks.
    define('GANTRYADMIN_PATH', GANTRY5_PATH . '/admin');
}

gantry5_register_private_theme_updaters();

// Force a one-time refresh when Gantry's theme updater behavior changes.
add_action('load-themes.php', 'gantry5_maybe_reset_theme_update_cache', 5);

// Let WordPress core handle Gantry theme updates (WordPress.org only).
add_action('load-themes.php', 'gantry5_refresh_wporg_theme_updates');

// Add Gantry 5 defaults on plugin activation.
register_activation_hook(__FILE__, 'gantry5_plugin_defaults');
add_action('admin_init', 'gantry5_plugin_defaults');

function gantry5_register_private_theme_updaters()
{
    $private_updaters = array(
        'g5_helium'   => get_theme_root() . '/g5_helium/private/theme-updates.php',
        'g5_hydrogen' => get_theme_root() . '/g5_hydrogen/private/theme-updates.php',
    );

    foreach ($private_updaters as $updater) {
        if (file_exists($updater)) {
            require_once $updater;
        }
    }
}

function gantry5_maybe_reset_theme_update_cache()
{
    if (!gantry5_has_installed_theme_updates()) {
        return;
    }

    $refresh_signature = 'private-theme-updaters-v1';
    $option_name = 'gantry5_theme_updates_refresh_signature';

    if (get_option($option_name) === $refresh_signature) {
        return;
    }

    delete_site_transient('update_themes');
    delete_site_transient('update_themes_last_checked');
    update_option($option_name, $refresh_signature);
}

function gantry5_has_installed_theme_updates()
{
    $gantry_themes = array('g5_helium', 'g5_hydrogen');
    $installed_themes = wp_get_themes();

    foreach ($gantry_themes as $slug) {
        if (isset($installed_themes[$slug])) {
            return true;
        }
    }

    return false;
}

function gantry5_refresh_wporg_theme_updates()
{
    if (!gantry5_has_installed_theme_updates()) {
        return;
    }

    $last_checked = (int) get_site_transient('update_themes_last_checked');
    if ($last_checked > 0 && (time() - $last_checked) < HOUR_IN_SECONDS) {
        return;
    }

    if (!function_exists('wp_update_themes')) {
        require_once ABSPATH . 'wp-admin/includes/update.php';
    }

    wp_update_themes();
    set_site_transient('update_themes_last_checked', time(), HOUR_IN_SECONDS);
}

function gantry5_plugin_defaults()
{
    $defaults = array(
        'production'       => '0',
        'use_media_folder' => '0',
        'assign_posts'     => '1',
        'assign_pages'     => '1',
        'debug'            => '0',
        'offline'          => '0',
        'offline_message'  => 'Site is currently in offline mode. Please try again later.',
        'cache_path'       => '',
        'compile_yaml'     => '1',
        'compile_twig'     => '1'
    );

    $option = (array)get_option('gantry5_plugin');

    update_option('gantry5_plugin', $option + $defaults);
}

add_filter('kses_allowed_protocols', 'gantry5_add_streams_to_kses');

function gantry5_add_streams_to_kses($protocols)
{
    $streams = array(
        'gantry-cache',
        'gantry-themes',
        'gantry-theme',
        'gantry-assets',
        'gantry-media',
        'gantry-engines',
        'gantry-engine',
        'gantry-layouts',
        'gantry-particles',
        'gantry-blueprints',
        'gantry-config',
        'wp-includes',
        'wp-content',
    );

    $protocols = array_merge($protocols, $streams);
    return $protocols;
}

// Initialize plugin language on init to avoid WP 6.7+ early translation notices.
add_action('init', 'gantry5_load_textdomain', 1);

function gantry5_load_textdomain()
{
    $domain = 'gantry5';
    $languages_path = basename(GANTRY5_PATH) . '/admin/languages';

    if (load_plugin_textdomain($domain, false, $languages_path) === false) {
        add_filter('plugin_locale', 'gantry5_modify_locale', 10, 2);
        load_plugin_textdomain($domain, false, $languages_path);
        remove_filter('plugin_locale', 'gantry5_modify_locale', 10);
    }
}

function gantry5_modify_locale($locale, $domain = null)
{
    // Revert the gantry5 domain locale to en_US
    if ($domain === 'gantry5' || $domain === 'nucleus') {
        $locale = 'en_US';
    }

    return $locale;
}

function gantry5_php_version_error()
{
    printf(
        '<div class="error"><p>%s</p></div>',
        sprintf(
            /* translators: 1: current PHP version, 2: required PHP version. */
            esc_html__('You are running PHP %1$s, but Gantry 5 Framework needs at least PHP %2$s to run.', 'gantry5'),
            esc_html(PHP_VERSION),
            '8.1.0'
        )
    );
}
// Preserve Gantry theme settings on update.
add_action('upgrader_pre_install', 'gantry5_backup_theme_settings', 10, 2);
add_action('upgrader_post_install', 'gantry5_restore_theme_settings', 10, 2);

function gantry5_backup_theme_settings($return, $hook_extra)
{
    if (!gantry5_is_managed_theme_update($hook_extra)) {
        return $return;
    }

    $theme = $hook_extra['theme'];
    $theme_dir = get_theme_root() . '/' . $theme;
    $backup_dir = WP_CONTENT_DIR . '/gantry-theme-backups/' . $theme;
    $filesystem = gantry5_get_filesystem();

    if (!$filesystem) {
        return $return;
    }

    if ($filesystem->is_dir($backup_dir)) {
        $filesystem->delete($backup_dir, true);
    }

    wp_mkdir_p($backup_dir);
    gantry5_copy_theme_settings_directory($theme_dir . '/custom', $backup_dir . '/custom', $filesystem);
    gantry5_copy_theme_settings_directory($theme_dir . '/config', $backup_dir . '/config', $filesystem);

    return $return;
}

function gantry5_restore_theme_settings($return, $hook_extra)
{
    if (!gantry5_is_managed_theme_update($hook_extra)) {
        return $return;
    }

    $theme = $hook_extra['theme'];
    $theme_dir = get_theme_root() . '/' . $theme;
    $backup_dir = WP_CONTENT_DIR . '/gantry-theme-backups/' . $theme;
    $filesystem = gantry5_get_filesystem();

    if (!$filesystem) {
        return $return;
    }

    gantry5_copy_theme_settings_directory($backup_dir . '/custom', $theme_dir . '/custom', $filesystem);
    gantry5_copy_theme_settings_directory($backup_dir . '/config', $theme_dir . '/config', $filesystem);

    return $return;
}

function gantry5_is_managed_theme_update($hook_extra)
{
    if (empty($hook_extra['theme'])) {
        return false;
    }

    return in_array($hook_extra['theme'], array('g5_helium', 'g5_hydrogen'), true);
}

function gantry5_get_filesystem()
{
    global $wp_filesystem;

    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    if (!WP_Filesystem()) {
        return false;
    }

    return $wp_filesystem;
}

function gantry5_copy_theme_settings_directory($source, $destination, $filesystem)
{
    if (!$filesystem->is_dir($source)) {
        return;
    }

    copy_dir($source, $destination);
}
