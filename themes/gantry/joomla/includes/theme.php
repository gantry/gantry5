<?php
class_exists('\\Gantry\\Framework\\Gantry') or die;

// Define the template.
class GantryTheme extends \Gantry\Framework\Theme {}

// Initialize theme stream.
$gantry['platform']->set(
    'streams.theme.prefixes',
    ['' => ["themes://{$gantry['theme.name']}", "themes://{$gantry['theme.name']}/common"]]
);

// Define Gantry services.
$gantry['theme'] = function ($c)  {
    return new GantryTheme($c['theme.path'], $c['theme.name']);
};
