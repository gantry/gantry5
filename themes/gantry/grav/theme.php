<?php
namespace Grav\Theme;

/** @var $grav */
/** @var $config */
/** @var $name */
/** @var $locator */

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once $locator('gantry-theme://includes/gantry.php');
if (!$gantry) {
    throw new \RuntimeException('Gantry Framework could not be loaded.');
}

// Define the template.
require $locator('gantry-theme://includes/class.php');

// Define Gantry services.
$gantry['theme'] = function ($c) use ($grav, $config, $name) {
    return new Gantry($grav, $config, $name);
};

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];
$theme->setLayout('gantry-theme://layouts/test.yaml');
print_r($gantry['platform']);
// Boot the service.
return $theme;
