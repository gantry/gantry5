<?php
defined('IN_PHPBB') or die;

define('DEBUG', false);

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';
$gantry['theme.path'] = __DIR__;

// Define the template.
class Theme extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ($c) use ($style) {
    return new Theme($c['theme.path'], $style);
};

// Return the service.
return $gantry;
