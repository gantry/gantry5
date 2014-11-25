<?php
define('STANDALONE_ROOT', dirname(dirname($_SERVER['SCRIPT_FILENAME'])));
define('THEME', basename(dirname($_SERVER['SCRIPT_FILENAME'])));

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';

// Boot the service.
$theme = $gantry['theme'];

// Render the page.
echo $theme->setLayout('gantry-theme://layouts/test.yaml')->render('index.html.twig');
