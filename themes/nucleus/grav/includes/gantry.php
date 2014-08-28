<?php

use Gantry\Framework\Gantry;

// Load Gantry Framework.
$bootstrap = __DIR__ . '/../src/bootstrap.php';
if (!is_file($bootstrap)) {
    throw new LogicException( 'Symbolic links missing, please see README.md in your theme!' );
}

require_once $bootstrap;

$path = (string) $path;

// Get Gantry instance.
$gantry = Gantry::instance();

// Set the theme path from Grav variable.
$gantry['theme.path'] = $path;

// Boot Gantry locator.
$gantry['locator'];

return $gantry;
