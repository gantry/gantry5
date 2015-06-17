<?php
use Gantry\Component\Filesystem\Folder;

define('PRIME_ROOT', dirname($_SERVER['SCRIPT_FILENAME']));
define('PRIME_URI', dirname($_SERVER['SCRIPT_NAME']));

date_default_timezone_set('UTC');

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once PRIME_ROOT . '/includes/gantry.php';

// Get current theme and path.
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : Folder::getRelativePath($_SERVER['REQUEST_URI'], PRIME_URI);
$path = explode('?', $path, 2);
$path = reset($path);
$extension = strrchr(basename($path), '.');
if ($extension) {
    $path = substr($path, 0, -strlen($extension));
}
$theme = strpos($path, 'admin') !== 0 ? Folder::shift($path) : null;

define('THEME', $theme);
define('PAGE_PATH', $path ?: ($theme ? 'home' : ''));
define('PAGE_EXTENSION', trim($extension, '.') ?: 'html');

// Bootstrap selected theme.
$include = PRIME_ROOT . "/themes/{$theme}/includes/gantry.php";
if (is_file($include)) {
    include $include;
}

// Enter to administration if we are in /ROOT/theme/admin. Also display installed themes if no theme has been selected.
if (!isset($gantry['theme']) || strpos($path, 'admin') === 0) {
    require_once PRIME_ROOT . '/admin/admin.php';
    exit();
}

// Boot the service.
/** @var Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

try {
    // Render the page.
    echo $theme->render('@pages/' . PAGE_PATH . '.' . PAGE_EXTENSION . '.twig');
} catch (Twig_Error_Loader $e) {
    // Or display error if template file couldn't be found.
    echo $theme->render('@pages/_error.html.twig', ['error' => $e]);
}
