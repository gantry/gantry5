<?php
defined('_JEXEC') or die;

// Define the template.
class GantryTemplate extends \Gantry\Framework\Theme {}

// Initialize theme stream.
$gantry['platform']->set(
    'streams.theme.prefixes',
    ['' => ["themes://{$gantry['theme.name']}", "themes://{$gantry['theme.name']}/common"]]
);

// Define Gantry services.
$gantry['theme'] = function ($c)  {
    return new GantryTemplate($c['theme.path'], $c['theme.name']);
};
