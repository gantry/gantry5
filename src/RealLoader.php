<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
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

        define('GANTRY5_VERSION', '5.4.29');
        define('GANTRY5_VERSION_DATE', 'June 21, 2019');

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        define('GANTRY_DEBUGGER', class_exists('Gantry\\Debugger'));

        return self::autoload();
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     * @throws \LogicException
     * @internal
     */
    protected static function autoload()
    {
        // Register platform specific overrides.
        if (defined('JVERSION') && defined('JPATH_ROOT')) {
            define('GANTRY5_PLATFORM', 'joomla');
            define('GANTRY5_ROOT', JPATH_ROOT);
        } elseif (defined('WP_DEBUG') && defined('ABSPATH')) {
            define('GANTRY5_PLATFORM', 'wordpress');
            if (class_exists('Env') && defined('CONTENT_DIR')) {
                // Bedrock support.
                define('GANTRY5_ROOT', preg_replace('|' . preg_quote(CONTENT_DIR). '$|', '', WP_CONTENT_DIR));
            } else {
                // Plain WP support.
                define('GANTRY5_ROOT', dirname(WP_CONTENT_DIR));
            }
        } elseif (defined('GRAV_VERSION') && defined('ROOT_DIR')) {
            define('GANTRY5_PLATFORM', 'grav');
            define('GANTRY5_ROOT', rtrim(ROOT_DIR, '/'));
        } elseif (defined('PRIME_ROOT')) {
            define('GANTRY5_PLATFORM', 'prime');
            define('GANTRY5_ROOT', PRIME_ROOT);
        } else {
            throw new \RuntimeException('Gantry: CMS not detected!');
        }

        $base = __DIR__;
        $vendor = "{$base}/platforms/" . GANTRY5_PLATFORM;
        $dev = is_dir($vendor);
        if (!$dev) {
            $vendor = $base;
        }
        $autoload = "{$vendor}/vendor/autoload.php";

        // Initialize auto-loading.
        if (!file_exists($autoload)) {
            throw new \LogicException('Please run composer in Gantry 5 Library!');
        }

        /** @var \Composer\Autoload\ClassLoader $loader */
        $loader = require_once $autoload;

        if ($dev) {
            $loader->addPsr4('Gantry\\', "{$base}/classes/Gantry");
        }

        return $loader;
    }
}
