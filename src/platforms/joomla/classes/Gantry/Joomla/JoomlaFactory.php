<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

use Joomla\CMS\Factory as JFactory;

/**
 * Class JoomlaFactory
 * @package Gantry\Joomla
 */
abstract class JoomlaFactory
{

    /**
     * @return \Joomla\CMS\User\User
     * @since 5.5
     */
    public static function getUser()
    {
        return JFactory::getUser();
    }

    /**
     * @return \Joomla\CMS\Application\CMSApplicationInterface
     * @since 5.5
     */
    public static function getApplication()
    {
        return JFactory::getApplication();
    }

    /**
     * @return \Joomla\CMS\Session\Session
     * @since 5.5
     */
    public static function getSession()
    {
        return JFactory::getSession();
    }

    /**
     * @return \Joomla\CMS\Document\Document
     * @since 5.5
     */
    public static function getDocument()
    {
        return JFactory::getDocument();
    }

    /**
     * @return \Joomla\Registry\Registry
     * @since 5.5
     */
    public static function getConfig()
    {
        return JFactory::getConfig();
    }

    /**
     * @return \Joomla\Database\DatabaseDriver
     * @since 5.5
     */
    public static function getDbo()
    {
        return JFactory::getDbo();
    }

    /**
     * @return \Joomla\CMS\Language\Language
     * @since 5.5
     */
    public static function getLanguage()
    {
        return JFactory::getLanguage();
    }
}
