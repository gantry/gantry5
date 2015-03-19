<?php
defined( 'ABSPATH' ) or die;

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once __DIR__ . '/includes/gantry.php';
if ( !$gantry ) {
    return;
}

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry[ 'theme' ];

// Boot the service.
$theme->setLayout( 'test' );
