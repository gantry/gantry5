<?php

if (!defined('GANTRY5_VERSION')) {

    // Initialize auto-loading.
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        /** @var \Composer\Autoload\ClassLoader $classLoader */
        $classLoader = require_once __DIR__ . '/vendor/autoload.php';
    } else {
        throw new LogicException('Please run composer in Gantry Library!');
    }

    /**
     * @name GANTRY_VERSION
     */
    define('GANTRY5_VERSION', '${project.version}');

    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }

    if (!defined('DEBUG')) {
        define('DEBUG', true);
    }

    // Enable tracy.
    if (DEBUG) {
        \Tracy\Debugger::enable();
    }

    // Load all the independent functions.
    require_once __DIR__ . '/includes/functions.php';
    // Register platform specific classes.
    if (defined('JVERSION')) {
        $classLoader->add('\\Gantry\\Platform', __DIR__ . '/Gantry/Platforms/Joomla');
    } elseif (defined('WP_DEBUG')) {
        $classLoader->add('\\Gantry\\Platform', __DIR__ . '/Gantry/Platforms/WordPress');
    } elseif (defined('GRAV_VERSION')) {
        $classLoader->add('\\Gantry\\Platform', __DIR__ . '/Gantry/Platforms/Grav');
    }
}
