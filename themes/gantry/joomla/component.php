<?php
defined('_JEXEC') or die;

// Bootstrap the template.
$gantry = include_once __DIR__ . '/includes/template.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$raw = JRequest::getString('type') == 'raw';

// Render the component.
echo $theme->render($raw ? 'raw.html.twig' : 'component.html.twig');
