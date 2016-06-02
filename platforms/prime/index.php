<?php
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Gantry;

define('PRIME_PROFILER', false);

define('PRIME_ROOT', dirname($_SERVER['SCRIPT_FILENAME']));
define('PRIME_URI', dirname($_SERVER['SCRIPT_NAME']));

PRIME_PROFILER && profiler_enable();

date_default_timezone_set('UTC');

// Load debugger if it exists.
$include = PRIME_ROOT . '/debugbar/Debugger.php';
if (file_exists($include)) {
    include_once $include;
}

// Bootstrap Gantry framework.
include_once PRIME_ROOT . '/src/bootstrap.php';

// Initialize Gantry.
$gantry = Gantry::instance();

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

GANTRY_DEBUGGER && \Gantry\Debugger::startTimer('render', 'Rendering page');

try {
    // Render the page.
    echo $theme->setLayout('default')->render('@pages/' . PAGE_PATH . '.' . PAGE_EXTENSION . '.twig');
} catch (Twig_Error_Loader $e) {
    // Or display error if template file couldn't be found.
    echo $theme->setLayout('_error')->render('@pages/_error.html.twig', ['error' => $e]);
}

PRIME_PROFILER && profiler_results();

/*
 * Enable profiler.
 */
function profiler_enable()
{
    if (!function_exists('xhprof_enable')) return;

    xhprof_enable(XHPROF_FLAGS_NO_BUILTINS);
}

/**
 * Display profiler results.
 */
function profiler_results()
{
    if (!function_exists('xhprof_disable')) return;

    $info = xhprof_disable();

    $treshholds = [
        '#660000' => 500,
        '#880000' => 370,
        '#AA0000' => 250,
        '#CC0000' => 180,
        '#CC2200' => 120,
        '#CC4400' => 80,
        '#CC6600' => 55,
        '#CC8800' => 35,
        '#CCAA00' => 25,
        '#CCCC00' => 18,
        '#AACC00' => 12,
        '#88CC00' => 9,
        '#66CC00' => 6,
        '#44CC00' => 4,
        '#22CC00' => 3,
        '#00CC00' => 2,
        '' => 1
    ];
    asort($treshholds);

    echo "<h1>Profiler Information</h1>";
    echo '<div style="padding:0 2em">';
    foreach ($info as $call => $data) {
        $count = $data['ct'];
        $time = $data['wt'] / 1000;
        $color = '';
        foreach ($treshholds as $color => $treshhold) {
            if ((float) $time < (float) $treshhold) {
                break;
            }
        }
        if (!$color) {
            continue;
        }
        echo sprintf(
            "<font color='%s'><b>%0.3f</b> ms</font> (<b>%d</b> calls): <i>%s</i><br/>\n",
            $color, $time, $count, $call
        );
    }
    echo "</div>";
}