<?php
defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include __DIR__ . '/includes/gantry.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$context = [
    'formtoken' => JHtml::_('form.token')
];

// Render the page.
echo $theme
    ->setLayout('offline')
    ->render('offline.html.twig', $context);
