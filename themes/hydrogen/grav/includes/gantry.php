<?php
namespace Gantry\Framework;

/** @var $locator */
/** @var $path */

if (!class_exists('\Gantry5\Loader')) {
    throw new \LogicException('Please install Gantry 5 Framework plugin!');
}

// Setup Gantry 5 Framework or throw exception.
\Gantry5\Loader::setup();

// Get Gantry instance.
$gantry = Gantry::instance();

// Set the theme path from Grav variable.
$gantry['theme.path'] = $locator('theme://');
$gantry['theme.name'] = basename($gantry['theme.path']);

// Initialize theme stream.
$gantry['platform']->set(
    'streams.gantry-theme.prefixes',
    ['' => [
        "gantry-themes://{$gantry['theme.name']}/custom",
        "gantry-themes://{$gantry['theme.name']}",
        "gantry-themes://{$gantry['theme.name']}/common"
    ]]
);

$gantry['streams'];

return $gantry;
