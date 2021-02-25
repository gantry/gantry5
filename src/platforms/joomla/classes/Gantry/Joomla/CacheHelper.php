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
        self::cleanByType('com_templates');
        self::cleanByType('_system');
    }

    public static function cleanMenu()
    {
        self::cleanByType('mod_menu');
        self::cleanByType('_system');
    }

    public static function cleanPlugin()
    {
        self::cleanByType('_system', 0);
        self::cleanByType('_system', 1);
        self::cleanByType('com_plugins', 0);
        self::cleanByType('com_plugins', 1);
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
