<?php
namespace Gantry\Theme;

defined('IN_PHPBB') or die;

define('DEBUG', false);

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';
$gantry['theme.path'] = __DIR__;
$gantry['theme.name'] = $style;

// Define the template.
class Theme extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Theme($c['theme.path'], $c['theme.name']);
};

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];
$theme->setLayout('theme://layouts/test.yaml');

// Return the service.
return $gantry;
