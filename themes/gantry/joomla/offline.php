<?php
defined('_JEXEC') or die;

// Bootstrap the template.
$gantry = include_once __DIR__ . '/includes/template.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$context = [
    'formtoken' => JHtml::_('form.token')
];

// Render the page.
echo $theme
    ->setLayout('theme://layouts/offline.yaml')
    ->render('offline.html.twig', $context);
