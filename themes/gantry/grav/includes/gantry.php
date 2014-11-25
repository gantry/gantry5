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
$gantry['theme.id'] = 0;
$gantry['theme.path'] = $locator('theme://');
$gantry['theme.name'] = basename($gantry['theme.path']);
$gantry['theme.params'] = [];

// Initialize theme stream.
$gantry['platform']->set(
    'streams.gantry-theme.prefixes',
    ['' => ["gantry-themes://{$gantry['theme.name']}", "gantry-themes://{$gantry['theme.name']}/common"]]
);

$gantry['streams'];

return $gantry;
