<?php
namespace Grav\Theme;

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once __DIR__ . '/includes/gantry.php';
if (!$gantry) {
    throw new \RuntimeException('Gantry Framework could not be loaded');
}

// Define the template.
require_once __DIR__ . '/includes/class.php';

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Nucleus(__DIR__, basename(__FILE__, '.php'));
};
$gantry['config'] = function ($c) {
    return \Gantry\Framework\Config::instance(CACHE_DIR . 'gantry5/config.php', $c['theme']->path);
};

// Boot the service.
return $gantry['theme'];
