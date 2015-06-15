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

// Initialize theme stream.
$gantry['platform']->set(
    'streams.gantry-theme.prefixes',
    ['' => [
        "gantry-themes://{$gantry['theme.name']}/custom",
        "gantry-themes://{$gantry['theme.name']}",
        "gantry-themes://{$gantry['theme.name']}/common"
    ]]
);

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Theme($c['theme.path'], $c['theme.name']);
};

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];
$theme->setLayout('default');

// Return the service.
return $gantry;
