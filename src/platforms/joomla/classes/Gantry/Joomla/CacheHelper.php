<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Factory;

/**
 * Class CacheHelper
 * @package Gantry\Joomla
 */
class CacheHelper
{
    public static function cleanTemplates()
    {
        static::cleanSystem();
        self::cleanByType('com_templates', 0);
        self::cleanByType('com_templates', 1);
    }

    public static function cleanModules()
    {
        static::cleanSystem();
        self::cleanByType('com_modules', 0);
    }

    public static function cleanMenu()
    {
        static::cleanSystem();
        self::cleanByType('mod_menu', 0);
        self::cleanByType('com_menus', 0);
        self::cleanByType('com_menus', 1);
    }

    public static function cleanPlugin()
    {
        static::cleanSystem();
        self::cleanByType('com_plugins', 0);
        self::cleanByType('com_plugins', 1);
    }

    public static function cleanSystem()
    {
        self::cleanByType('_system', 0);
        self::cleanByType('_system', 1);
    }

    /**
     * @param string|null $group
     * @param int $client_id
     * @param string $event
     */
    private static function cleanByType($group = null, $client_id = 0, $event = 'onContentCleanCache')
    {
        $config = Factory::getConfig();

        $options = [
            'defaultgroup' => $group,
            'cachebase' => $client_id ? JPATH_ADMINISTRATOR . '/cache' : $config->get('cache_path', JPATH_SITE . '/cache'),
            'result' => true
        ];

        try {
            /** @var Cache $cache */
            $cache = Cache::getInstance('callback', $options);
            $cache->clean();
        } catch (CacheExceptionInterface $e) {
            $options['result'] = false;
        }

        /** @var CMSApplication $application */
        $application = Factory::getApplication();

        // Trigger the onContentCleanCache event.
        $application->triggerEvent($event, $options);
    }
}
