<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

class Gantry extends Base\Gantry
{
    /**
     * @param string $location
     * @param bool   $force
     * @return array
     */
    public function styles($location = 'head', $force = false)
    {
        // Do not display head, Joomla will take care of it (most of the time).
        return (!$force && $location == 'head') ? [] : parent::styles($location);
    }

    /**
     * @param string $location
     * @param bool $force
     * @return array
     */
    public function scripts($location = 'head', $force = false)
    {
        // Do not display head, Joomla will take care of it (most of the time).
        return (!$force && $location == 'head') ? [] : parent::scripts($location);
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

        $container['menu'] = function ($c) {
            return new Menu;
        };

        $container['page'] = function ($c) {
            return new Page($c);
        };

        return $container;
    }
}
