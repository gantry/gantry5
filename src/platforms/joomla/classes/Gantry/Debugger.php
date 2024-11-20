<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry;

use Joomla\CMS\Log\Log;

/**
 * Class Debugger
 */
class Debugger
{
    /** @var static */
    protected static $instance;

    /**
     * @return static
     */
    public static function instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Dump variables into the Messages tab of the Debug Bar.
     *
     * @param        $message
     * @param string $label
     */
    public static function addMessage($message, $label = 'info', $isString = true)
    {
        if (\is_object($message) || \is_array($message)) {
            $message = \json_encode($message);
        }

        Log::add($message, Log::{strtoupper($label)}, 'gantry5');

        return static::instance();
    }

    public static function startTimer($name, $description = null)
    {
        return static::instance();
    }

    public static function stopTimer($name)
    {
        return static::instance();
    }

    public static function setLocator($locator)
    {
        return static::instance();
    }

    public static function setErrorHandler()
    {
        return static::instance();
    }

    public static function setConfig($config)
    {
        return static::instance();
    }
}
