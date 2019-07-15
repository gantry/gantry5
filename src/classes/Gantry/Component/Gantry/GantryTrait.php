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

namespace Gantry\Component\Gantry;

use Gantry\Framework\Gantry;

trait GantryTrait
{
    /**
     * @var Gantry
     */
    private static $gantry;

    /**
     * Get global Gantry instance.
     *
     * @return Gantry
     */
    public static function gantry()
    {
        // We cannot set variable directly for the trait as it doesn't work in HHVM.
        if (!self::$gantry) {
            self::$gantry = Gantry::instance();
        }

        return self::$gantry;
    }
}
