<?php
defined('_JEXEC') or die;

// Bootstrap the template.
$gantry = include_once __DIR__ . '/includes/template.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

// Render the page.
echo $theme->render('comingsoon.html.twig');
