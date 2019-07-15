<?php
namespace Gantry;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Gantry\Component\Config\Config;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Debugger
 * @package Gantry\Component\Debug
 */
class Debugger
{
    protected static $instance;

    /** @var JavascriptRenderer $renderer */
    protected static $renderer;

    /** @var StandardDebugBar $debugbar */
    protected static $debugbar;

    /**
     * @return static
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    /**
     * Initialize debugbar.
     */
    public function __construct()
    {
        if (!class_exists('DebugBar\\StandardDebugBar')) {
            $include = __DIR__ . '/vendor/autoload.php';
            if (!file_exists($include)) {
                return;
            }

            include_once $include;
        }

        self::$debugbar = new StandardDebugBar();
        self::$debugbar['time']->addMeasure('Loading', self::$debugbar['time']->getRequestStartTime(), microtime(true));
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
        if (self::$debugbar) {
            self::$debugbar->addCollector(new ConfigCollector($config->toArray(), 'Config'));
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
        if (self::$debugbar) {
            $paths = $locator->getPaths(null);
            $paths && self::$debugbar->addCollector(new ConfigCollector($paths, 'Streams'));
        }

        return static::instance();
    }
    
    /**
     * Add the debugger assets to the Grav Assets.
     *
     * @return static
     */
    public static function assets()
    {
        if (self::$debugbar) {
            $gantry = Gantry::instance();

            $gantry->load('jquery');

            self::$renderer = self::$debugbar->getJavascriptRenderer();
            self::$renderer->setIncludeVendors(false);

            self::$renderer->setBaseUrl(rtrim(PRIME_URI, '/') . '/debugbar/vendor/maximebf/debugbar/src/DebugBar/Resources');
            list($css_files, $js_files) = self::$renderer->getAssets(null, JavascriptRenderer::RELATIVE_URL);

            /** @var Document $document */
            $document = $gantry['document'];
            foreach ($css_files as $css) {
                $document->addHeaderTag([
                    'tag' => 'link',
                    'rel' => 'stylesheet',
                    'href' => $css
                ], 'head', 0);
            }

            foreach ($js_files as $js) {
                $document->addHeaderTag([
                    'tag' => 'script',
                    'src' => $js
                ], 'head', 0);
            }
        }

        return static::instance();
    }

    /**
     * Adds a data collector.
     *
     * @param $collector
     * @return static
     * @throws \DebugBar\DebugBarException
     */
    public static function addCollector($collector)
    {
        if (self::$debugbar) {
            self::$debugbar->addCollector($collector);
        }

        return static::instance();
    }

    /**
     * Returns a data collector.
     *
     * @param $collector
     *
     * @return \DebugBar\DataCollector\DataCollectorInterface|null
     * @throws \DebugBar\DebugBarException
     */
    public static function getCollector($collector)
    {
        if (!self::$debugbar) {
            return null;
        }

        return self::$debugbar->getCollector($collector);
    }

    /**
     * Displays the debug bar.
     *
     * @return string
     */
    public static function render()
    {
        if (!self::$debugbar) {
            return '';
        }

        return self::$renderer->render();
    }

    /**
     * Sends the data through the HTTP headers.
     *
     * @return static
     */
    public static function sendDataInHeaders()
    {
        if (self::$debugbar) {
            self::$debugbar->sendDataInHeaders();
        }

        return static::instance();
    }

    /**
     * Start a timer with an associated name and description.
     *
     * @param             $name
     * @param string|null $description
     * @return static
     */
    public static function startTimer($name, $description = null)
    {
        if (self::$debugbar) {
            self::$debugbar['time']->startMeasure($name, $description);
        }

        return static::instance();
    }

    /**
     * Stop the named timer.
     *
     * @param string $name
     * @return static
     */
    public static function stopTimer($name)
    {
        if (self::$debugbar) {
           self::$debugbar['time']->stopMeasure($name);
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
        if (self::$debugbar) {
            self::$debugbar['messages']->addMessage($message, $label, $isString);
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
        if (self::$debugbar) {
            self::$debugbar['exceptions']->addException($e);
        }

        return static::instance();
    }
}

