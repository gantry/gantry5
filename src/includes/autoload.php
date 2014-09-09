<?php

$base = dirname(__DIR__);

// Initialize auto-loading.
if (file_exists($base . '/../vendor/autoload.php')) {
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require_once $base . '/../vendor/autoload.php';
} else {
    throw new LogicException('Please run composer in Gantry Library!');
}

// Register platform specific overrides.
if (defined('JVERSION')) {
    define('GANTRY5_ROOT', JPATH_ROOT);
    $loader->addPsr4('Gantry\\', $base . '/classes/Gantry/Platform/Joomla', true);
} elseif (defined('WP_DEBUG')) {
    define('GANTRY5_ROOT', ABSPATH);
    $loader->addPsr4('Gantry\\', $base . '/classes/Gantry/Platform/WordPress', true);
} elseif (defined('GRAV_VERSION')) {
    define('GANTRY5_ROOT', rtrim(ROOT_DIR, '/'));
    $loader->addPsr4('Gantry\\', $base . '/classes/Gantry/Platform/Grav', true);
}

return $loader;
