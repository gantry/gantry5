<?php

class_exists('\\Gantry\\Framework\\Gantry') or die;

/**
 * Define the template.
 */
class GantryTheme extends \Gantry\Framework\Theme {}

// Initialize theme stream.
/** @var \Gantry\Framework\Platform $platform */
$platform = $gantry['platform'];
$platform->set(
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
$platform->set(
    'streams.gantry-custom.prefixes',
    ['' => [
        "gantry-prime://custom/{$gantry['theme.name']}",
        ]
    ]
);

// Define Gantry services.
$gantry['theme'] = static function($c)  {
    return new GantryTheme($c['theme.path'], $c['theme.name']);
};
