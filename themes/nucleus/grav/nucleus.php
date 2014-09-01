<?php
namespace Grav\Theme;

/** @var $locator */
/** @var $grav */
/** @var $config */
/** @var $name */

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once $locator('theme://includes/gantry.php');
if (!$gantry) {
    throw new \RuntimeException('Gantry Framework could not be loaded.');
}

// Define the template.
require $locator('theme://includes/class.php');

// Define Gantry services.
$gantry['theme'] = function ($c) use ($grav, $config, $name) {
    return new Nucleus($grav, $config, $name);
};

// Boot the service.
return $gantry['theme'];
