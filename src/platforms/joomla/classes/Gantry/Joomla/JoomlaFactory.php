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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;

/**
 * Class JoomlaFactory
 * @package Gantry\Joomla
 */
abstract class JoomlaFactory
{

    /**
     * @param string|int|null $id
     * @return User
     * @since 5.5
     */
    public static function getUser($id = null)
    {
        return Factory::getUser($id);
    }

    /**
     * @param null $id
     * @param array $config
     * @param string $prefix
     * @return CMSApplication
     * @throws \Exception
     * @since 5.5
     */
    public static function getApplication($id = null, array $config = [], $prefix = 'J')
    {
        return Factory::getApplication($id, $config, $prefix);
    }

    /**
     * @param array $options
     * @return Session
     * @since 5.5
     */
    public static function getSession(array $options = [])
    {
        return Factory::getSession($options);
    }

    /**
     * @return Document
     * @since 5.5
     */
    public static function getDocument()
    {
        return Factory::getDocument();
    }

    /**
     * @param string|null $file
     * @param string $type
     * @param string $namespace
     * @return Registry
     * @since 5.5
     */
    public static function getConfig($file = null, $type = 'PHP', $namespace = '')
    {
        return Factory::getConfig($file, $type, $namespace);
    }

    /**
     * @return \JDatabaseDriver
     * @since 5.5
     */
    public static function getDbo()
    {
        return Factory::getDbo();
    }

    /**
     * @return Language
     * @since 5.5
     */
    public static function getLanguage()
    {
        return Factory::getLanguage();
    }

    /**
     * @param string $time
     * @param mixed|null $tzOffset
     * @return Date
     * @since 5.5
     */
    public static function getDate($time = 'now', $tzOffset = null)
    {
        return Factory::getDate($time, $tzOffset);
    }
}
