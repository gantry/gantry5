<?php
namespace Grav\Theme;

/** @var $grav */
/** @var $config */
/** @var $name */
/** @var $locator */

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once $locator('theme://includes/gantry.php');
if (!$gantry) {
    throw new \RuntimeException('Gantry Framework could not be loaded.');
}

// Define the template.
require $locator('theme://includes/class.php');

// Define Gantry services.
$gantry['theme'] = function ($c) use ($grav, $config, $name) {
    return new Gantry($grav, $config, $name);
};

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];
$theme->setLayout('theme://layouts/test.yaml');

// Boot the service.
return $theme;
