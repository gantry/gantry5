<?php
/**
 * Private distribution only: remote update metadata for the Helium theme.
 */

defined('ABSPATH') || die;

add_filter('pre_set_site_transient_update_themes', 'g5_helium_private_theme_updates');

/**
 * Normalize Gantry theme versions for developer builds.
 *
 * @param string $version Theme version string.
 * @return string
 */
function g5_helium_private_normalize_version($version)
{
    $version = trim((string) $version);

    return preg_replace('/-dev(?:-[0-9a-z.]+)?$/i', '', $version);
}

/**
 * Inject update metadata for the private Helium distribution.
 *
 * @param object $transient Theme update transient.
 * @return object
 */
function g5_helium_private_theme_updates($transient)
{
    if (!is_object($transient)) {
        $transient = new stdClass();
    }

    if (!isset($transient->response) || !is_array($transient->response)) {
        $transient->response = array();
    }

    $slug = 'g5_helium';
    $url = 'https://updates.gantry.org/wp-updates/g5_helium_copy.json';
    $theme = wp_get_theme($slug);

    if (!$theme->exists()) {
        return $transient;
    }

    $request = wp_remote_get(
        $url,
        array(
            'timeout' => 10,
        )
    );

    if (is_wp_error($request)) {
        return $transient;
    }

    $status_code = (int) wp_remote_retrieve_response_code($request);
    if ($status_code !== 200) {
        return $transient;
    }

    $payload = json_decode(wp_remote_retrieve_body($request), true);
    if (!is_array($payload) || empty($payload['version']) || empty($payload['download_url'])) {
        return $transient;
    }

    $installed_version = (string) $theme->get('Version');
    $available_version = (string) $payload['version'];
    $installed_stable_version = g5_helium_private_normalize_version($installed_version);
    $available_stable_version = g5_helium_private_normalize_version($available_version);
    $is_dev_build = $installed_version !== $installed_stable_version;

    $has_direct_update = version_compare($installed_version, $available_version, '<');
    $has_stable_update = version_compare($installed_stable_version, $available_stable_version, '<');
    $has_dev_to_stable_update = $is_dev_build
        && version_compare($installed_stable_version, $available_stable_version, '<=')
        && $installed_version !== $available_version;

    if (!$has_direct_update && !$has_stable_update && !$has_dev_to_stable_update) {
        return $transient;
    }

    $transient->response[$slug] = array(
        'theme'       => $slug,
        'new_version' => $available_stable_version ?: $available_version,
        'url'         => !empty($payload['homepage'])
            ? esc_url_raw($payload['homepage'])
            : (!empty($payload['details_url']) ? esc_url_raw($payload['details_url']) : ''),
        'package'     => esc_url_raw($payload['download_url']),
    );

    return $transient;
}
