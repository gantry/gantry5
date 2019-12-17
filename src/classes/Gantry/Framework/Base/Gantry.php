<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Base;

use Gantry\Component\Config\Config;
use Gantry\Component\System\Messages;
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

abstract class Gantry extends Container
{
    /**
     * @var static
     */
    protected static $instance;
    protected $wrapper;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = static::init();

            if (!defined('GANTRY5_DEBUG')) {
                define('GANTRY5_DEBUG', self::$instance->debug());
            }
        }

        return self::$instance;
    }

    public static function restart()
    {
        self::$instance = null;

        return static::instance();
    }

    /**
     * Returns true if debug mode has been enabled.
     *
     * @return boolean
     */
    public function debug()
    {
        return $this['global']->get('debug', false);
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
        $gantry = Gantry::instance();

        return $gantry['document']->siteUrl();
    }

    /**
     * @param string $location
     * @return array
     */
    public function styles($location = 'head')
    {
        return $this['document']->getStyles($location);
    }

    /**
     * @param string $location
     * @return array
     */
    public function scripts($location = 'head')
    {
        return $this['document']->getScripts($location);
    }

    /**
     * Load Javascript framework / extension in platform independent way.
     *
     * @param string $framework
     * @return bool
     */
    public function load($framework)
    {
        return $this['document']->addFramework($framework);
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
            $this[$id] = function () use ($value) {
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
        return $events->dispatch($eventName, $event);
    }

    public function route($path)
    {
        $routes = $this->offsetGet('routes');
        $route = isset($routes[$path]) ? $routes[$path] : $routes[1];

        if (!$route) {
            // TODO: need to implement back to root in Prime..
            return $this->offsetGet('base_url');
        }

        $path = implode('/', array_filter(func_get_args(), function($var) { return isset($var) && $var !== ''; }));

        // rawurlencode() the whole path, but keep the slashes.
        $path = preg_replace(['|%2F|', '|%25|'], ['/', '%'], rawurlencode($path));

        return preg_replace('|/+|', '/', '/' . $this->offsetGet('base_url') . sprintf($route, $path));
    }

    public function authorize($action, $id = null)
    {
        return $this['platform']->authorize($action, $id);
    }

    public function wrapper($value = null)
    {
        if ($value !== null ) {
            $this->wrapper = $value;
        }

        return $this->wrapper;
    }

    protected static function init()
    {
        /** @var Gantry $instance */
        $instance = new static();

        if (GANTRY_DEBUGGER) {
            $instance['debugger'] = \Gantry\Debugger::instance();
        }

        $instance['loader'] = \Gantry5\Loader::get();

        $instance->register(new ConfigServiceProvider);
        $instance->register(new StreamsServiceProvider);

        $instance['request'] = function () {
            return new Request;
        };

        $instance['events'] = function () {
            return new EventDispatcher;
        };

        $instance['platform'] = function ($c) {
            return new Platform($c);
        };

        $instance['translator'] = function () {
            return new Translator;
        };

        $instance['site'] = function () {
            return new Site;
        };

        $instance['menu'] = function () {
            return new Menu;
        };

        $instance['messages'] = function () {
            return new Messages;
        };

        $instance['page'] = function ($c) {
            return new Page($c);
        };

        $instance['document'] = function () {
            return new Document;
        };

        // Make sure that nobody modifies the original collection by making it a factory.
        $instance['outlines'] = $instance->factory(function ($c) {
            static $collection;
            if (!$collection) {
                $collection = (new Outlines($c))->load();
            }

            return $collection->copy();
        });

        // @deprecated 5.3
        $instance['configurations'] = $instance->factory(function ($c) {
            GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Depredated call: gantry.configurations");

            static $collection;
            if (!$collection) {
                $collection = (new Outlines($c))->load();
            }

            return $collection->copy();
        });

        $instance['positions'] = $instance->factory(function ($c) {
            static $collection;
            if (!$collection) {
                $collection = (new Positions($c))->load();
            }

            return $collection->copy();
        });

        $instance['global'] = function ($c) {
            $data = $c->loadGlobal() + [
                    'debug' => false,
                    'production' => true,
                    'use_media_folder' => false,
                    'asset_timestamps' => true,
                    'asset_timestamps_period' => 7,
                    'compile_yaml' => true,
                    'compile_twig' => true
                ];

            return new Config($data);
        };

        return $instance;
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
        // If requested version is smaller than 5.0-rc, it's not compatible.
        if (version_compare($version, '5.0-rc', '<')) {
            return false;
        }

        // Development version support.
        if ($version === '5.3' || static::isDev()) {
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
        if ('@version@' == GANTRY5_VERSION) {
            return true;
        }
        if ('dev-' === substr(GANTRY5_VERSION, 0, 4)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    protected function loadGlobal()
    {
        return [];
    }
}
