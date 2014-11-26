<?php
define('STANDALONE_ROOT', dirname($_SERVER['SCRIPT_FILENAME']));
define('STANDALONE_URI', dirname($_SERVER['SCRIPT_NAME']));

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once STANDALONE_ROOT . '/includes/gantry.php';

// Get current theme and path.
$path = explode('/', Gantry\Component\Filesystem\Folder::getRelativePath($_SERVER['REQUEST_URI'], STANDALONE_URI), 2);
$theme = array_shift($path);
$path = trim(array_shift($path), '/') ?: 'index';

// Bootstrap selected theme.
$include = STANDALONE_ROOT . "/themes/{$theme}/includes/gantry.php";

if (is_file($include)) {
    include $include;
}

if (!isset($gantry['theme']) || strpos($path, 'admin') === 0) {
    // Enter to administration.
    require_once STANDALONE_ROOT . '/admin/admin.php';
    exit();
}

// Boot the service.
/** @var Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

// Render the page.
try {
    echo $theme->setLayout('gantry-theme://layouts/test.yaml')->render($path . '.html.twig');
} catch (Twig_Error_Loader $e) {
    echo $theme->setLayout('gantry-theme://layouts/error.yaml')->render('_error.html.twig', ['error' => $e]);
}
