<?php
define('STANDALONE_ROOT', dirname($_SERVER['SCRIPT_FILENAME']));
define('STANDALONE_URI', dirname($_SERVER['SCRIPT_NAME']));

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once STANDALONE_ROOT . '/includes/gantry.php';

// Get current theme and path.
$path = explode('/', Gantry\Component\Filesystem\Folder::getRelativePath($_SERVER['REQUEST_URI'], STANDALONE_URI), 2);
$theme = array_shift($path);
$path = trim(array_shift($path), '/') ?: 'index';

define('THEME', $theme);
define('PAGE_PATH', $path);

// Bootstrap selected theme.
$include = STANDALONE_ROOT . "/themes/{$theme}/includes/gantry.php";
if (is_file($include)) {
    include $include;
}

// Enter to administration if we are in /ROOT/theme/admin. Also display installed themes if no theme has been selected.
if (!isset($gantry['theme']) || strpos($path, 'admin') === 0) {
    require_once STANDALONE_ROOT . '/admin/admin.php';
    exit();
}

// Boot the service.
/** @var Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

try {
    // Render the page.
    echo $theme->setLayout('gantry-theme://layouts/test.yaml')->render($path . '.html.twig');
} catch (Twig_Error_Loader $e) {
    // Or display error if template file couldn't be found.
    echo $theme->setLayout('gantry-theme://layouts/error.yaml')->render('_error.html.twig', ['error' => $e]);
}
