<?php
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
