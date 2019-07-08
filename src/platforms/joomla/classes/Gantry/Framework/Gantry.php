<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Joomla\JoomlaFactory;

class Gantry extends Base\Gantry
{
    /**
     * @return boolean
     */
    public function debug()
    {
        return JDEBUG;
    }

    /**
     * @return boolean
     */
    public function admin()
    {
        return JoomlaFactory::getApplication()->isClient('administrator');
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

        // Trigger the event.
        // FIXME: Joomla 4: check this out
        JoomlaFactory::getApplication()->triggerEvent('onGantryGlobalConfig', ['global' => &$global]);

        return $global;
    }
}
