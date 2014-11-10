<?php
defined('_JEXEC') or die;

// Define the template.
class GantryTemplate extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ($c)  {
    return new GantryTemplate($c['theme.path'], $c['theme.name']);
};
