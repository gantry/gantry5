<?php
namespace Grav\Theme;

use Grav\Common\Registry;
use Grav\Common\Filesystem\File;
use Gantry\Theme\Theme;

// Initialize Gantry.
$bootstrap = __DIR__ . '/src/bootstrap.php';

if (!is_file($bootstrap))
{
    throw new \LogicException('Symbolic links missing, please see README.md in your theme!');
}

require_once $bootstrap;
require_once __DIR__ . '/class.php';

$theme = new Nucleus(__DIR__, basename(__FILE__, '.php'));

// Instantiate Gantry.
$gantry = \Gantry\Gantry::instance();
$gantry->initialize($theme);

return $theme;
