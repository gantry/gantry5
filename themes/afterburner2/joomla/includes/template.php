<?php
defined('_JEXEC') or die;

// Define the template.
class AfterburnerTemplate extends \Gantry\Framework\Theme {}

// Initialize theme stream.
$gantry['platform']->set(
    'streams.theme.prefixes',
    ['' => [
        "themes://{$gantry['theme.name']}",
        "themes://{$gantry['theme.name']}/common",
        "themes://gantry",
        "themes://gantry/common"]
    ]
);

// Define Gantry services.
$gantry['theme'] = function ($c)  {
    return new AfterburnerTemplate($c['theme.path'], $c['theme.name']);
};
