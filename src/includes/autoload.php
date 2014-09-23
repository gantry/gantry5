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
} elseif (class_exists('Mage')) {
    define('GANTRY5_ROOT', Mage::getBaseDir('base'));
    $loader->addPsr4('Gantry\\', $base . '/classes/Gantry/Platform/Magento', true);
} elseif (defined('STANDALONE_ROOT')) {
    define('GANTRY5_ROOT', STANDALONE_ROOT);
    $loader->addPsr4('Gantry\\', $base . '/classes/Gantry/Platform/Standalone', true);
}

return $loader;
