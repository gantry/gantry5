<?php
// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';

$gantry['theme.path'] = __DIR__;
$gantry['theme.name'] =  basename(__DIR__);

// Define the template.
class Theme extends \Gantry\Framework\Theme {}

$self = $this;

$gantry['page'] = function ($c) use ($self) {
    return new \Gantry\Framework\Page($c, $self);
};

// Initialize theme stream.
$gantry['platform']->set(
    'streams.gantry-theme.prefixes',
    ['' => ["gantry-themes://{$gantry['theme.name']}/default", "gantry-themes://{$gantry['theme.name']}/default/common"]]
);

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Theme($c['theme.path'], $c['theme.name']);
};

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

// Render the page.
echo $theme
    ->setLayout('default')
    ->render('index.html.twig');
