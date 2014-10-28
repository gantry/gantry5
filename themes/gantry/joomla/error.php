<?php
defined('_JEXEC') or die;

// Bootstrap the template.
$gantry = include_once __DIR__ . '/includes/template.php';

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
    ->setLayout('theme://layouts/error.yaml')
    ->render('error.html.twig', $context);
