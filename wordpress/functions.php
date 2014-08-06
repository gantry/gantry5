<?php
defined( 'ABSPATH' ) or die;

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once __DIR__ . '/includes/gantry.php';
if ( !$gantry ) {
    return;
}

// Define the template.
class Nucleus extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ( $c ) {
    return new Nucleus(__DIR__, 'nucleus');
};
$gantry['config'] = function ( $c ) {
    $path = $c['theme']->path;
    return \Gantry\Framework\Config::instance( $path . '/cache/config.php', $path );
};

// Boot the service.
$theme = $gantry['theme'];

