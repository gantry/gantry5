<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry5;

use Composer\Autoload\ClassLoader;

/**
 * Class Loader
 * @package Gantry5
 */
abstract class Loader
{
    /** @var ClassLoader */
    private static $loader;

    /**
     * @return void
     */
    public static function setup()
    {
        self::get();
    }

    /**
     * @return ClassLoader
     */
    public static function get()
    {
        if (null === self::$loader) {
            require_once __DIR__ . '/RealLoader.php';
            self::$loader = RealLoader::getClassLoader();
        }

        return self::$loader;
    }
}
