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

use Gantry\Component\Config\Config;
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

        // Use locator from Grav.
        $container['locator'] = function($c) {
            return Grav::instance()['locator'];
        };

        $container['site'] = function ($c) {
            return new Site;
        };

        $container['menu'] = function ($c) {
            return new Menu;
        };

        $container['page'] = function ($c) {
            return new Page($c);
        };

        $container['global'] = function ($c) {
            return new Config([]);
        };

        return $container;
    }
}
