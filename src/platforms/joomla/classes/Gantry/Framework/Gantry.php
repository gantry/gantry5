<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

/**
 * Class Gantry
 * @package Gantry\Framework
 */
class Gantry extends Base\Gantry
{
    /**
     * @return bool
     */
    public function debug()
    {
        return JDEBUG;
    }

    /**
     * @return bool
     */
    public function admin()
    {
        /** @var CMSApplication $application */
        $app = Factory::getApplication();

        return $app->isClient('administrator');
    }

    /**
     * @param string $location
     * @param bool   $force
     * @return array
     */
    public function styles($location = 'head', $force = false)
    {
        // Do not display head, Joomla will take care of it (most of the time).
        return (!$force && $location === 'head') ? [] : parent::styles($location);
    }

    /**
     * @param string $location
     * @param bool $force
     * @return array
     */
    public function scripts($location = 'head', $force = false)
    {
        // Do not display head, Joomla will take care of it (most of the time).
        return (!$force && $location === 'head') ? [] : parent::scripts($location);
    }

    /**
     * @return array
     */
    protected function loadGlobal()
    {
        $global = null;

        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Trigger the event.
        $app->triggerEvent('onGantryGlobalConfig', ['global' => &$global]);

        return $global;
    }
}
