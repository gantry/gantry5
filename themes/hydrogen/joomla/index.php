<?php
defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include __DIR__ . '/includes/gantry.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

// Get the template
$template = \JFactory::getApplication()->getTemplate(true);

// Render the page.
echo $theme
    ->setLayout($template->id)
    ->render('index.html.twig');

