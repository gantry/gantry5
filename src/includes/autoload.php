<?php

$base = dirname(__DIR__);

// Initialize auto-loading.
if (file_exists($base . '/../vendor/autoload.php')) {
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require_once $base . '/../vendor/autoload.php';
} else {
    throw new LogicException('Please run composer in Gantry Library!');
}

// Load all the independent functions.
require_once __DIR__ . '/functions.php';

// Register platform specific overrides.
if (defined('JVERSION')) {
    $loader->addPsr4('Gantry\\', $base . '/classes/Gantry/Platform/Joomla', true);
} elseif (defined('WP_DEBUG')) {
    $loader->addPsr4('Gantry\\', $base . '/classes/Gantry/Platform/WordPress', true);
} elseif (defined('GRAV_VERSION')) {
    $loader->addPsr4('Gantry\\', $base . '/classes/Gantry/Platform/Grav', true);
}

return $loader;
