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
     * @throws \LogicException
     */
    protected static function load()
    {
        $container = parent::load();

        // Use locator from Grav.
        $container['locator'] = function($c) {
            return Grav::instance()['locator'];
        };

        $container['global'] = function ($c) {
            $grav = Grav::instance();
            $config = $grav['config']->get('plugins.gantry5');

            return new Config($config);
        };

        return $container;
    }
}
