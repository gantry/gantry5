<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\System\Messages;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Framework\Services\StreamsServiceProvider;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\Event\DispatcherInterface;
use RocketTheme\Toolbox\Event\EventDispatcher;

/**
 * Class Gantry
 * @package Gantry\Framework
 */
class Gantry extends Base\Gantry
{
    /**
     * @return static
     */
    protected static function init(): Gantry
    {
        $instance = new static();

        $instance->register(new ConfigServiceProvider());
        $instance->register(new StreamsServiceProvider());

        $instance['request'] = static function (): Request {
            return new Request();
        };

        $instance['events'] = static function (): EventDispatcher {
            return new EventDispatcher();
        };

        $instance['platform'] = static function ($c): Platform {
            return new Platform($c);
        };

        $instance['translator'] = static function (): Translator {
            return new Translator();
        };

        $instance['site'] = static function (): Site {
            return new Site();
        };

        $instance['menu'] = static function (): Menu {
            return new Menu();
        };

        $instance['messages'] = static function (): Messages {
            return new Messages();
        };

        $instance['page'] = static function ($c): Page {
            return new Page($c);
        };

        $instance['document'] = static function (): Document {
            return new Document();
        };

        // Make sure that nobody modifies the original collection by making it a factory.
        $instance['outlines'] = $instance->factory(static function ($c): Outlines {
            static $collection;
            if (!$collection) {
                $collection = (new Outlines($c))->load();
            }

            return $collection->copy();
        });

        $instance['positions'] = $instance->factory(static function ($c): Positions {
            static $collection;
            if (!$collection) {
                $collection = (new Positions($c))->load();
            }

            return $collection->copy();
        });

        $instance['global'] = static function (Gantry $c): Config {
            $data = $c->loadGlobal() + [
                    'debug'                   => false,
                    'production'              => true,
                    'use_media_folder'        => false,
                    'asset_timestamps'        => true,
                    'asset_timestamps_period' => 7,
                    'compile_yaml'            => true,
                    'compile_twig'            => true,
                    'offline_message'         => ''
                ];

            return new Config($data);
        };

        return $instance;
    }

    /**
     * @return bool
     */
    public function debug()
    {
        return JDEBUG;
    }

    /**
     * @return bool
     */
    public function admin(): bool
    {
        return Factory::getApplication()->isClient('administrator');
    }

    /**
     * @param ?string $location
     * @param ?bool   $force
     * @return array
     */
    public function styles($location = 'head', $force = false): array
    {
        // Do not display head, Joomla will take care of it (most of the time).
        return (!$force && $location === 'head') ? [] : parent::styles($location);
    }

    /**
     * @param ?string $location
     * @param ?bool $force
     * @return array
     */
    public function scripts($location = 'head', $force = false): array
    {
        // Do not display head, Joomla will take care of it (most of the time).
        return (!$force && $location === 'head') ? [] : parent::scripts($location);
    }

    /**
     * @return array
     */
    protected function loadGlobal(): array
    {
//TODO: replace with component settings insted of a plugin?!?!

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);

        $global = $dispatcher->dispatch('onGantryGlobalConfig', AbstractEvent::create('onGantryGlobalConfig', [
            'subject' => $this,
        ]))->getArgument('global', []);

        return $global;
    }
}
