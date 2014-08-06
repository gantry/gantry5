<?php
defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully.
$gantry = include_once __DIR__ . '/includes/gantry.php';
if (!$gantry) {
    return;
}

// Define the template.
class Nucleus extends \Gantry\Framework\Theme {}

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Nucleus(__DIR__, $this->template);
};
$gantry['config'] = function ($c) {
    return \Gantry\Framework\Config::instance(JPATH_CACHE . '/gantry5/config.php', $c['theme']->path);
};

// Boot the service.
$theme = $gantry['theme'];

echo $theme->render('index.html.twig');
