<?php

class_exists('\\Gantry\\Framework\\Gantry') or die;

/**
 * Define the template.
 */
class GantryTheme extends \Gantry\Framework\Theme {}

// Initialize theme stream.
$gantry['platform']->set(
    'streams.gantry-theme.prefixes',
    ['' => [
        "gantry-custom://",
        "gantry-themes://{$gantry['theme.name']}",
        "gantry-themes://{$gantry['theme.name']}/common",
        "gantry-themes://hydrogen",
        "gantry-themes://hydrogen/common"
        ]
    ]
);
$gantry['platform']->set(
    'streams.gantry-custom.prefixes',
    ['' => [
        "gantry-prime://custom/{$gantry['theme.name']}",
        ]
    ]
);

// Define Gantry services.
$gantry['theme'] = function($c)  {
    return new GantryTheme($c['theme.path'], $c['theme.name']);
};
