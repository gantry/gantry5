<?php
defined( 'ABSPATH' ) or die;

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once __DIR__ . '/includes/gantry.php';
if ( !$gantry ) {
    return;
}

$gantry['theme.path'] = __DIR__;

// Define the template.
class Nucleus extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ( $c ) {
    return new Nucleus( $c[ 'theme.path' ], get_option( 'template' ) );
};

// Boot the service.
$theme = $gantry['theme'];
