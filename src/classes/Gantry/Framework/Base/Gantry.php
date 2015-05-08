<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Base;

use Gantry\Framework\Configurations;
use Gantry\Framework\Platform;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Framework\Services\StreamsServiceProvider;
use Gantry\Framework\Translator;
use RocketTheme\Toolbox\DI\Container;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventDispatcher;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Gantry extends Container
{
    /**
     * @var static
     */
    protected static $instance;
    protected $wrapper;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = static::load();
        }

        return self::$instance;
    }

    /**
     * @param string $location
     * @return array
     */
    public function styles($location = 'head')
    {
        return Document::getStyles($location);
    }

    /**
     * @param string $location
     * @return array
     */
    public function scripts($location = 'head')
    {
        return Document::getScripts($location);
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

        // Create a dummy service.
        $this[$id] = function () use ($value) {
            return $value;
        };

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

        return preg_replace('|/+|', '/', '/' . $this->offsetGet('base_url') . sprintf($route, $path));
    }

    public function wrapper($value = null)
    {
        if ($value !== null ) {
            $this->wrapper = $value;
        }

        return $this->wrapper;
    }

    protected static function load()
    {
        /** @var Gantry $instance */
        $instance = new static();

        $instance->register(new ConfigServiceProvider);
        $instance->register(new StreamsServiceProvider);

        $instance['events'] = function ($c) {
            return new EventDispatcher;
        };

        $instance['platform'] = function ($c) {
            return new Platform($c);
        };

        $instance['translator'] = function ($c) {
            return new Translator;
        };

        // Make sure that nobody modifies the original collection by making it a factory.
        $instance['configurations'] = $instance->factory(function ($c) {
            static $collection;
            if (!$collection) {
                $collection = (new Configurations($c))->load();
            }

            return $collection->copy();
        });

        return $instance;
    }
}
