<?php
defined('_JEXEC') or die;

// Define the template.
class AfterburnerTemplate extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ($c)  {
    return new AfterburnerTemplate($c['theme.path'], $c['theme.name']);
};
