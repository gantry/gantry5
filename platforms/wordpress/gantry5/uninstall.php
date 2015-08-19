<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

global $wp_filesystem;

// Remove cache.
$wp_filesystem->rmdir(WP_CONTENT_DIR . '/cache/gantry5', true);

// Remove options.
delete_option( 'gantry5_plugin' );
