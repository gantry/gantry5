<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry5;

/**
 * Use \Gantry5\Loader::setup() or \Gantry5\Loader::get() instead.
 *
 * This class separates Loader logic from the \Gantry5\Loader class. By adding this extra class we are able to upgrade
 * Gantry5 and initializing the new version during a single request -- as long as Gantry5 has not been initialized.
 *
 * @internal
 */
abstract class RealLoader
{
    protected static $errorMessagePhpMin = 'You are running PHP %s, but Gantry 5 Framework needs at least PHP %s to run.';
    protected static $errorMessageGantryLoaded = 'Attempting to load Gantry 5 Framework multiple times.';

    /**
     * Initializes Gantry5 and returns Composer ClassLoader.
     *
     * @return \Composer\Autoload\ClassLoader
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public static function getClassLoader()
    {
        // Fail safe version check for PHP <5.4.0.
        if (version_compare($phpVersion = PHP_VERSION, '5.4.0', '<')) {
            throw new \RuntimeException(sprintf(self::$errorMessagePhpMin, $phpVersion, '5.4.0'));
        }

        if (defined('GANTRY5_VERSION')) {
            throw new \LogicException(self::$errorMessageGantryLoaded);
        }

        define('GANTRY5_VERSION', '5.0.0-rc.1');

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        if (!defined('GANTRY5_DEBUG')) {
            define('GANTRY5_DEBUG', false);
        }

        return self::autoload();
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     * @throws \LogicException
     * @internal
     */
    protected static function autoload()
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
        if (defined('JVERSION') && defined('JPATH_ROOT')) {
            define('GANTRY5_ROOT', JPATH_ROOT);
            $dev ? $loader->addPsr4('Gantry\\', $base . '/platforms/joomla', true) : null;
        } elseif (defined('WP_DEBUG') && defined('ABSPATH')) {
            define('GANTRY5_ROOT', ABSPATH);
            $dev ? $loader->addPsr4('Gantry\\', $base . '/platforms/wordpress', true) : null;
        } elseif (defined('GRAV_VERSION') && defined('ROOT_DIR')) {
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
