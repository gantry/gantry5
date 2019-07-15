<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Grav\Common\Grav;

class Gantry extends Base\Gantry
{
    /**
     * @throws \LogicException
     */
    protected static function init()
    {
        $container = parent::init();

        // Use locator from Grav.
        $container['locator'] = function() {
            return Grav::instance()['locator'];
        };

        return $container;
    }

    /**
     * @return array
     */
    protected function loadGlobal()
    {
        $grav = Grav::instance();
        return (array) $grav['config']->get('plugins.gantry5');
    }
}
