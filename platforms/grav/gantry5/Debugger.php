<?php
namespace Gantry;
use DebugBar\DataCollector\ConfigCollector;
use Gantry\Component\Config\Config;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Debugger
 * @package Gantry\Component\Debug
 */
class Debugger
{
    /**
     * @var Debugger
     */
    protected static $instance;

    /**
     * @var \Grav\Common\Debugger
     */
    protected static $debugger;

    /**
     * Debugger constructor.
     */
    public function __construct()
    {
        static::$debugger = \Grav\Common\Grav::instance()['debugger'];
    }

    /**
     * @return static
     */
    public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Start a timer with an associated name and description
     *
     * @param             $name
     * @param string|null $description
     *
     * @return static
     */
    public static function startTimer($name, $description = null)
    {
        static::$debugger->startTimer("g5_{$name}", "Gantry: {$description}");

        return static::instance();
    }

    /**
     * Stop the named timer
     *
     * @param string $name
     *
     * @return static
     */
    public static function stopTimer($name)
    {
        static::$debugger->stopTimer("g5_{$name}");

        return static::instance();
    }

    /**
     * Add the debugger assets to the Grav Assets.
     *
     * @return static
     */
    public static function assets()
    {
        return static::instance();
    }

    /**
     * Displays the debug bar
     *
     * @return string
     */
    public static function render()
    {
        // Return nothing as Grav handles rendering for us.
        return '';
    }

    /**
     * Sends the data through the HTTP headers
     *
     * @return static
     */
    public static function sendDataInHeaders()
    {
        if (static::$debugger && method_exists(static::$debugger, 'sendDataInHeaders')) {
            static::$debugger->sendDataInHeaders();
        }

        return static::instance();
    }

    /**
     * Returns collected debugger data.
     *
     * @return array|null
     */
    public static function getData()
    {
        return static::$debugger && method_exists(static::$debugger, 'getData') ? static::$debugger->getData() : null;
    }

    /**
     * Returns a data collector.
     *
     * @param $collector
     *
     * @return \DebugBar\DataCollector\DataCollectorInterface
     * @throws \DebugBar\DebugBarException|null
     */
    public static function getCollector($collector)
    {
        if (static::$debugger && method_exists(static::$debugger, 'getCollector')) {
            return static::$debugger->getCollector($collector);
        }

        return null;
    }

    /**
     * Adds a data collector.
     *
     * @param $collector
     *
     * @return static
     * @throws \DebugBar\DebugBarException
     */
    public static function addCollector($collector)
    {
        if (static::$debugger && method_exists(static::$debugger, 'addCollector')) {
            static::$debugger->addCollector($collector);
        }

        return static::instance();
    }

    /**
     * Dump variables into the Messages tab of the Debug Bar.
     *
     * @param        $message
     * @param string $label
     * @return static
     */
    public static function addMessage($message, $label = 'info', $isString = true)
    {
        if (static::$debugger) {
            static::$debugger->addMessage($message, $label, $isString);
        }

        return static::instance();
    }

    /**
     * Dump exception.
     *
     * @param \Exception $e
     * @return Debugger
     */
    public static function addException(\Exception $e)
    {
        if (static::$debugger && method_exists(static::$debugger, 'addException')) {
            static::$debugger->addException($e);
        }

        return static::instance();
    }

    /**
     * Set Configuration
     *
     * @param Config $config
     * @return static
     * @throws \DebugBar\DebugBarException
     */
    public static function setConfig(Config $config)
    {
        if (static::$debugger) {
            static::$debugger->addCollector(new ConfigCollector($config->toArray(), 'Gantry'));
        }

        return static::instance();
    }

    /**
     * Set Configuration
     *
     * @param UniformResourceLocator $locator
     * @return static
     * @throws \DebugBar\DebugBarException
     */
    public static function setLocator(UniformResourceLocator $locator)
    {
        static $exists = false;

        if (static::$debugger) {
            $paths = $locator->getPaths(null);
            if ($paths) {
                if (!$exists) {
                    static::$debugger->addCollector(new ConfigCollector($paths, 'Streams'));
                } else {
                    static::$debugger->getCollector('Streams')->setData($paths);
                }
            }
            $exists = true;
        }

        return static::instance();
    }
}

