<?php
namespace Gantry\Framework;

use Grav\Common\Grav;

class Gantry extends Base\Gantry
{
    /**
     * @throws \LogicException
     */
    protected static function load()
    {
        $container = parent::load();

        $container['site'] = function ($c) {
            return new Site;
        };

        // Use locator from Grav.
        $container['locator'] = function($c) {
             return Grav::instance()['locator'];
        };

        return $container;
    }
}
