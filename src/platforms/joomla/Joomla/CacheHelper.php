<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
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

    private static function cleanByType($group)
    {
        $conf = \JFactory::getConfig();
        $dispatcher = \JEventDispatcher::getInstance();

        $options = array(
            'defaultgroup' => $group,
            'cachebase' => $conf->get('cache_path', JPATH_SITE . '/cache')
        );

        $cache = \JCache::getInstance('callback', $options);
        $cache->clean();

        // Trigger the onContentCleanCache event.
        $dispatcher->trigger('onContentCleanCache', $options);
    }
}
