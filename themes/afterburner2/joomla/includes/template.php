<?php
defined('_JEXEC') or die;

// Define the template.
class AfterburnerTemplate extends \Gantry\Framework\Theme {}

// Initialize theme stream.
$gantry['platform']->set(
    'streams.gantry-theme.prefixes',
    ['' => [
        "gantry-themes://{$gantry['theme.name']}",
        "gantry-themes://{$gantry['theme.name']}/common",
        "gantry-themes://gantry",
        "gantry-themes://gantry/common"]
    ]
);

// Define Gantry services.
$gantry['theme'] = function ($c)  {
    return new AfterburnerTemplate($c['theme.path'], $c['theme.name']);
};
