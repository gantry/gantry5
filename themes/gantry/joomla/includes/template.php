<?php
defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include __DIR__ . '/gantry.php';

// Define the template.
class GantryTemplate extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ($c)  {
    return new GantryTemplate($c['theme.path'], $c['theme.name']);
};

return $gantry;
