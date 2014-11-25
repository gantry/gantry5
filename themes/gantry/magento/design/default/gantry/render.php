<?php
// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';

$gantry['theme.id'] = 0;
$gantry['theme.path'] = __DIR__;
$gantry['theme.name'] =  basename(__DIR__);
$gantry['theme.params'] = [];

// Define the template.
class Theme extends \Gantry\Framework\Theme {}

$self = $this;

$gantry['page'] = function ($c) use ($self) {
    return new \Gantry\Framework\Page($c, $self);
};

// Initialize theme stream.
$gantry['platform']->set(
    'streams.theme.prefixes',
    ['' => ["themes://{$gantry['theme.name']}/default", "themes://{$gantry['theme.name']}/default/common"]]
);

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Theme($c['theme.path'], $c['theme.name']);
};

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

// Render the page.
echo $theme
    ->setLayout('theme://layouts/test.yaml')
    ->render('index.html.twig');
