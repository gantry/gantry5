<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Base;

use Gantry5\Loader;
use Gantry\Component\Config\Config;
use Gantry\Component\System\Messages;
use Gantry\Debugger;
use Gantry\Framework\Document;
use Gantry\Framework\Menu;
use Gantry\Framework\Outlines;
use Gantry\Framework\Page;
use Gantry\Framework\Platform;
use Gantry\Framework\Positions;
use Gantry\Framework\Request;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Framework\Services\StreamsServiceProvider;
use Gantry\Framework\Site;
use Gantry\Framework\Translator;
use RocketTheme\Toolbox\DI\Container;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventDispatcher;

/**
 * Class Gantry
 * @package Gantry\Framework\Base
 */
abstract class Gantry extends Container
{
    /** @var static|null */
    protected static $instance;

    /** @var mixed */
    protected $wrapper;

    /**
     * @return static
     */
    public static function instance()
    {
        $instance = self::$instance;
        if (null === $instance) {
            $instance = static::restart();

            if (!defined('GANTRY5_DEBUG')) {
                define('GANTRY5_DEBUG', $instance->debug());
            }
        }

        return $instance;
    }

    /**
     * @return static
     */
    public static function restart()
    {
        self::$instance = static::init();

        return self::$instance;
    }

    /**
     * Returns true if debug mode has been enabled.
     *
     * @return boolean
     */
    public function debug()
    {
        /** @var Config $global */
        $global = $this['global'];

        return $global->get('debug', false);
    }

    /**
     * Returns true if we are in administration.
     *
     * @return boolean
     */
    public function admin()
    {
        return defined('GANTRYADMIN_PATH');
    }


    /**
     * @return string
     */
    public function siteUrl()
    {
        /** @var Document $document */
        $document = $this['document'];

        return $document::siteUrl();
    }

    /**
     * @param string $location
     * @return array
     */
    public function styles($location = 'head')
    {
        /** @var Document $document */
        $document = $this['document'];

        return $document::getStyles($location);
    }

    /**
     * @param string $location
     * @return array
     */
    public function scripts($location = 'head')
    {
        /** @var Document $document */
        $document = $this['document'];

        return $document::getScripts($location);
    }

    /**
     * Load Javascript framework / extension in platform independent way.
     *
     * @param string $framework
     * @return bool
     */
    public function load($framework)
    {
        /** @var Document $document */
        $document = $this['document'];

        return $document::addFramework($framework);
    }

    /**
     * Lock the variable against modification and return the value.
     *
     * @param string $id
     * @return mixed
     */
    public function lock($id)
    {
        $value = $this[$id];

        try {
            // Create a dummy service.
            $this[$id] = static function () use ($value) {
                return $value;
            };
        } catch (\RuntimeException $e) {
            // Services are already locked, so ignore the error.
        }

        // Lock the service and return value.
        return $this[$id];
    }

    /**
     * Fires an event with optional parameters.
     *
     * @param  string $eventName
     * @param  Event  $event
     * @return Event
     */
    public function fireEvent($eventName, Event $event = null)
    {
        /** @var EventDispatcher $events */
        $events = $this['events'];

        /** @var Event $event */
        $event = $events->dispatch($eventName, $event);

        return $event;
    }

    /**
     * @param string $path
     * @return string
     */
    public function route($path)
    {
        $routes = $this->offsetGet('routes');
        $route = isset($routes[$path]) ? $routes[$path] : $routes[1];

        if (!$route) {
            return $this->offsetGet('base_url');
        }

        $path = implode('/', array_filter(func_get_args(), static function($var) { return isset($var) && $var !== ''; }));

        // rawurlencode() the whole path, but keep the slashes.
        $path = preg_replace(['|%2F|', '|%25|'], ['/', '%'], rawurlencode($path));

        return preg_replace('|/+|', '/', '/' . $this->offsetGet('base_url') . sprintf($route, $path));
    }

    /**
     * @param string $action
     * @param string|null $id
     * @return bool
     */
    public function authorize($action, $id = null)
    {
        /** @var Platform $platform */
        $platform = $this['platform'];

        return $platform->authorize($action, $id);
    }

    /**
     * @param mixed|null $value
     * @return mixed|null
     */
    public function wrapper($value = null)
    {
        if ($value !== null) {
            $this->wrapper = $value;
        }

        return $this->wrapper;
    }

    /**
     * @return static
     */
    protected static function init()
    {
        $instance = new static();

        if (\GANTRY_DEBUGGER) {
            $instance['debugger'] = Debugger::instance();
        }

        $instance['loader'] = Loader::get();

        $instance->register(new ConfigServiceProvider);
        $instance->register(new StreamsServiceProvider);

        $instance['request'] = static function () {
            return new Request();
        };

        $instance['events'] = static function () {
            return new EventDispatcher();
        };

        $instance['platform'] = static function ($c) {
            return new Platform($c);
        };

        $instance['translator'] = static function () {
            return new Translator();
        };

        $instance['site'] = static function () {
            return new Site();
        };

        $instance['menu'] = static function () {
            return new Menu();
        };

        $instance['messages'] = static function () {
            return new Messages();
        };

        $instance['page'] = static function ($c) {
            return new Page($c);
        };

        $instance['document'] = static function () {
            return new Document();
        };

        // Make sure that nobody modifies the original collection by making it a factory.
        $instance['outlines'] = $instance->factory(static function ($c) {
            static $collection;
            if (!$collection) {
                $collection = (new Outlines($c))->load();
            }

            return $collection->copy();
        });

        // @deprecated 5.3
        $instance['configurations'] = $instance->factory(static function ($c) {
            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage('Depredated call: gantry.configurations');
            }

            static $collection;
            if (!$collection) {
                $collection = (new Outlines($c))->load();
            }

            return $collection->copy();
        });

        $instance['positions'] = $instance->factory(static function ($c) {
            static $collection;
            if (!$collection) {
                $collection = (new Positions($c))->load();
            }

            return $collection->copy();
        });

        $instance['global'] = static function (Gantry $c) {
            $data = $c->loadGlobal() + [
                    'debug' => false,
                    'production' => true,
                    'use_media_folder' => false,
                    'asset_timestamps' => true,
                    'asset_timestamps_period' => 7,
                    'compile_yaml' => true,
                    'compile_twig' => true,
                    'offline_message'  => ''
                ];

            return new Config($data);
        };

        return $instance;
    }

    /**
     * Unicode-safe version of PHP’s pathinfo() function.
     *
     * @link  https://www.php.net/manual/en/function.pathinfo.php
     *
     * @param string $path
     * @param int|null $flags
     * @return array|string
     */
    public static function pathinfo($path, $flags = null)
    {
        $path = str_replace(['%2F', '%5C'], ['/', '\\'], rawurlencode($path));

        if (null === $flags) {
            $info = pathinfo($path);
        } else {
            $info = pathinfo($path, (int)$flags);
        }

        if (is_array($info)) {
            return array_map('rawurldecode', $info);
        }

        return rawurldecode($info);
    }

    /**
     * Unicode-safe version of the PHP basename() function.
     *
     * @link  https://www.php.net/manual/en/function.basename.php
     *
     * @param string $path
     * @param string $suffix
     * @return string
     */
    public static function basename($path, $suffix = '')
    {
        return rawurldecode(basename(str_replace(['%2F', '%5C'], '/', rawurlencode($path)), $suffix));
    }

    /**
     * Check if Gantry is compatible with your theme / extension.
     *
     * This function can be used to make sure that user has installed Gantry version
     * that has been tested to work with your extension. All existing functions should
     * be backwards compatible, but each release can add some new functionality, which
     * you may want to use.
     *
     * <code>
     * if ($gantry->isCompatible('5.0.1')) {
     *      // You can do it in the new way.
     * } else {
     *     // Revert to the old way to display an error message.
     * }
     * </code>
     *
     * @param string $version Minimum required version.
     *
     * @return boolean Yes, if it is safe to use Gantry Framework.
     */
    public function isCompatible($version)
    {
        // Development version support.
        if ($this->isDev()) {
            return true;
        }

        // Check if future version is needed.
        if (version_compare($version, GANTRY5_VERSION, '>')) {
            return false;
        }

        return true;
    }

    /**
     * Check if Gantry is running from a Git repository or is a CI build.
     *
     * Developers tend to do their work directly in the Git repositories instead of
     * creating and installing new builds after every change. This function can be
     * used to check the condition and make sure we do not break users repository
     * by replacing files during upgrade.
     *
     * @return boolean True if Git repository or CI build is detected.
     */
    public function isDev()
    {
        return '@version@' === GANTRY5_VERSION || strpos(GANTRY5_VERSION, 'dev-') === 0;
    }

    /**
     * @return array
     */
    protected function loadGlobal()
    {
        return [];
    }
}
