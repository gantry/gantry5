<?php
// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';
$gantry['theme.path'] = __DIR__;

// Define the template.
class Theme extends \Gantry\Framework\Theme {}

$self = $this;

$gantry['page'] = function ($c) use ($self) {
    return new \Gantry\Framework\Page($self);
};

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Theme($c['theme.path'], basename(__DIR__));
};

// Boot the service.
$theme = $gantry['theme'];

// Render the page.
echo $theme->render('index.html.twig');
