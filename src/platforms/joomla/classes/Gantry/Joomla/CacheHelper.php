<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

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
        self::cleanByType('mod_plugins', 0);
        self::cleanByType('mod_plugins', 1);
    }

    private static function cleanByType($group = null, $client_id = 0, $event = 'onContentCleanCache')
    {
        $conf = \JFactory::getConfig();
        $dispatcher = \JEventDispatcher::getInstance();

        $options = array(
            'defaultgroup' => $group,
            'cachebase' => ($client_id) ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache'));

        $cache = \JCache::getInstance('callback', $options);
        $cache->clean();

        // Trigger the onContentCleanCache event.
        $dispatcher->trigger($event, $options);
    }
}
