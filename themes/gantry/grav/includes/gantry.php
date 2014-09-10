<?php
namespace Gantry\Framework;

/** @var $locator */
/** @var $path */

// Attempt to locate Gantry Framework if it hasn't already been loaded.
if (!class_exists('Gantry')) {
    $bootstrap = $locator('theme://src/bootstrap.php');
    if (!$bootstrap) {
        throw new \LogicException('Gantry Framework not found!');
    }

    // Load Gantry Framework.
    require_once $bootstrap;
}

// Get Gantry instance.
$gantry = Gantry::instance();

// Set the theme path from Grav variable.
$gantry['theme.path'] = $locator('theme://');
return $gantry;
