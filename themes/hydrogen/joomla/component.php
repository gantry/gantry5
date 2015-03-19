<?php
defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include __DIR__ . '/includes/gantry.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$raw = JRequest::getString('type') == 'raw';

// Render the component.
echo $theme
    ->setLayout('_body_only')
    ->render($raw ? 'raw.html.twig' : 'component.html.twig');
