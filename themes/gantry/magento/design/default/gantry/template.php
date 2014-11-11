<?php
// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';
$gantry['theme.path'] = __DIR__;

// Define the template.
class Nucleus extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Nucleus($c['theme.path'], basename(__DIR__));
};

return $gantry['theme'];
