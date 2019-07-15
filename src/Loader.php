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

abstract class Loader
{
    public static function setup()
    {
        self::get();
    }

    /**
     * @return mixed
     */
    public static function get()
    {
        static $loader;

        if (!$loader) {
            require_once __DIR__ . '/RealLoader.php';
            $loader = RealLoader::getClassLoader();
        }

        return $loader;
    }
}
