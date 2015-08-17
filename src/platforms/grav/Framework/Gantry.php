<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Grav\Common\Grav;

class Gantry extends Base\Gantry
{
    /**
     * @return boolean
     */
    public function debug()
    {
        // TODO:
        return true;
    }

    /**
     * @return boolean
     */
    public function admin()
    {
        return defined('GANTRYADMIN_PATH');
    }

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
