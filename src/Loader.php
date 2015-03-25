<?php
namespace Gantry5;

class Loader
{
    public $loader;

    public static function setup()
    {
        self::get();
    }

    public static function get()
    {
        static $instance;

        if (!$instance) {
            $instance = new Loader;
        }

        return $instance->loader;
    }

    protected function __construct()
    {
        $errorMessage = 'You are running PHP %s, but Gantry 5 Framework needs at least PHP %s to run.';

        // Fail safe version check for PHP <5.4.0.
        if (version_compare($phpVersion = PHP_VERSION, '5.4.0', '<')) {
            throw new \RuntimeException(sprintf($errorMessage, $phpVersion, '5.4.0'));
        }

        /**
         * @name GANTRY_VERSION
         */
        define('GANTRY5_VERSION', '5.0.0-DEV');

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        if (!defined('GANTRY5_DEBUG')) {
            define('GANTRY5_DEBUG', false);
        }

        $this->loader = $this->autoload();
    }

    protected function autoload()
    {
        $base = __DIR__;

        // Initialize auto-loading.
        if (!file_exists($base . '/vendor/autoload.php')) {
            throw new \LogicException('Please run composer in Gantry 5 Library!');
        }

        /** @var \Composer\Autoload\ClassLoader $loader */
        $loader = require_once $base . '/vendor/autoload.php';

        $dev = is_dir($base . '/platforms');

        // Register platform specific overrides.
        if (defined('JVERSION')) {
            define('GANTRY5_ROOT', JPATH_ROOT);
            $dev ? $loader->addPsr4('Gantry\\', $base . '/platforms/joomla', true) : null;
        } elseif (defined('WP_DEBUG')) {
            define('GANTRY5_ROOT', ABSPATH);
            $dev ? $loader->addPsr4('Gantry\\', $base . '/platforms/wordpress', true) : null;
        } elseif (defined('GRAV_VERSION')) {
            define('GANTRY5_ROOT', rtrim(ROOT_DIR, '/'));
            $dev ? $loader->addPsr4('Gantry\\', $base . '/platforms/grav', true) : null;
        } elseif (defined('MAGENTO_ROOT')) {
            define('GANTRY5_ROOT', MAGENTO_ROOT);
            $dev ? $loader->addPsr4('Gantry\\', $base . '/platforms/magento', true) : null;
        } elseif (defined('IN_PHPBB')) {
            global $phpbb_root_path;
            define('GANTRY5_ROOT', $phpbb_root_path);
        } elseif (defined('PRIME_ROOT')) {
            define('GANTRY5_ROOT', PRIME_ROOT);
            $dev ? $loader->addPsr4('Gantry\\', $base . '/platforms/prime', true) : null;
        }

        return $loader;
    }
}
