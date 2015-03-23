<?php
defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include __DIR__ . '/includes/gantry.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$context = array(
    'errorcode' => isset($this->error) ? $this->error->getCode() : null,
    'error' => isset($this->error) ? $this->error->getMessage() : null,
    'backtrace' => $this->debug ? $this->renderBacktrace() : null
);

// Render the page.
echo $theme
    ->setLayout('_error')
    ->render('error.html.twig', $context);
