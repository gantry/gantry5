<?php
namespace Grav\Theme;

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once __DIR__ . '/includes/gantry.php';
if (!$gantry) {
    throw new \RuntimeException('Gantry Framework could not be loaded');
}

// Define the template.
require 'gantry.theme://includes/class.php';

// Define Gantry services.
$gantry['theme'] = function ($c) use ($grav, $config, $name) {
    return new Nucleus($grav, $config, $name);
};

// Boot the service.
return $gantry['theme'];
