<?php
defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include __DIR__ . '/includes/gantry.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$context = [];

if (isset($this->error))
{
    $context['errorcode'] = $this->error->getCode();
    $context['error'] = $this->error->getMessage();
}

// Render the page.
echo $theme
    ->setLayout('error')
    ->render('error.html.twig', $context);
