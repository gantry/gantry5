<?php
defined( 'ABSPATH' ) or die;

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once __DIR__ . '/includes/gantry.php';
if ( !$gantry ) {
    return;
}

$gantry['theme.path'] = __DIR__;
$gantry['theme.name'] = get_option( 'template' );

// Define the template.
class Theme extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ( $c ) {
    return new Theme( $c[ 'theme.path' ], $c[ 'theme.name' ] );
};

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

// Boot the service.
$theme->setLayout('theme://layouts/test.yaml');
