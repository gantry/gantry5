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

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\Event\DispatcherInterface;

/**
 * Class CacheHelper
 * @package Gantry\Joomla
 */
class CacheHelper
{
    public static function cleanTemplates(): void
    {
        static::cleanSystem();
        self::cleanByType('com_templates', 0);
        self::cleanByType('com_templates', 1);
    }

    public static function cleanModules(): void
    {
        static::cleanSystem();
        self::cleanByType('com_modules', 0);
    }

    public static function cleanMenu(): void
    {
        static::cleanSystem();
        self::cleanByType('mod_menu', 0);
        self::cleanByType('com_menus', 0);
        self::cleanByType('com_menus', 1);
    }

    public static function cleanPlugin(): void
    {
        static::cleanSystem();
        self::cleanByType('com_plugins', 0);
        self::cleanByType('com_plugins', 1);
    }

    public static function cleanSystem(): void
    {
        self::cleanByType('_system', 0);
        self::cleanByType('_system', 1);
    }

    /**
     * @param ?string|null $group
     * @param ?int $client_id
     * @param ?string $event
     */
    private static function cleanByType($group = null, $client_id = 0, $event = 'onContentCleanCache'): void
    {
        $app = Factory::getApplication();

        $options = [
            'defaultgroup' => $group,
            'cachebase'    => $client_id ? JPATH_ADMINISTRATOR . '/cache' : $app->get('cache_path', JPATH_CACHE),
            'result'       => true,
        ];

        try {
            /** @var CallbackController $cache */
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
                ->createCacheController('callback', $options);
            $cache->clean();
        } catch (CacheExceptionInterface $exception) {
            $options['result'] = false;
        }

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);

        $dispatcher->dispatch($event, new Model\AfterCleanCacheEvent($event, $options));
    }
}
