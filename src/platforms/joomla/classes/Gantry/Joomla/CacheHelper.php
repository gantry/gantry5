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
     * @throws \Exception
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
            // FIXME: Joomla 4
            $cache = \JCache::getInstance('callback', $options);
            $cache->clean();
        } catch (\Exception $e) { // FIXME: Joomla 3.7 uses JCacheException, Joomla 4?
            $options['result'] = false;
        }

        // Trigger the onContentCleanCache event.
        $application = Factory::getApplication();
        $application->triggerEvent($event, $options);
    }
}
