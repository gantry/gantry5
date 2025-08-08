<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Grav\Common\Config\Config;
use Grav\Common\Grav;

/**
 * Class Gantry
 * @package Gantry\Framework
 */
class Gantry extends Base\Gantry
{
    /**
     * @throws \LogicException
     */
    protected static function init()
    {
        $container = parent::init();

        // Use locator from Grav.
        $container['locator'] = static function() {
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

        /** @var Config $config */
        $config = $grav['config'];

        return (array) $config->get('plugins.gantry5');
    }
}
