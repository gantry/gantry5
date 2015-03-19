<?php
// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';
$gantry['theme.path'] = __DIR__;
$gantry['theme.name'] = 'hydrogen';

// Define the template.
class Nucleus extends \Gantry\Framework\Theme {}

// Initialize theme stream.
$gantry['platform']->set(
    'streams.gantry-theme.prefixes',
    ['' => ["gantry-themes://{$gantry['theme.name']}/default", "gantry-themes://{$gantry['theme.name']}/default/common"]]
);

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Nucleus($c['theme.path'], $c['theme.name']);
};

return $gantry['theme'];
