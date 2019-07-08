<?php
namespace Gantry;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Gantry\Component\Config\Config;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Joomla\CMS\Uri\Uri;
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

    protected static $errorHandler;

    protected static $deprecations = [];

    /**
     * @return static
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new static;
            self::setErrorHandler();
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
     * Add the debugger assets to the Gantry Assets.
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

            self::$renderer->setBaseUrl(Uri::root(true) . '/plugins/system/gantry5_debugbar/vendor/maximebf/debugbar/src/DebugBar/Resources');
            list($css_files, $js_files) = self::$renderer->getAssets(null, JavascriptRenderer::RELATIVE_URL);

            /** @var Document $document */
            $document = $gantry['document'];
            foreach ($css_files as $css) {
                $document::addHeaderTag([
                    'tag' => 'link',
                    'rel' => 'stylesheet',
                    'href' => $css
                ], 'head', 0);
            }

            foreach ($js_files as $js) {
                $document::addHeaderTag([
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

        self::addDeprecations();

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
            self::addDeprecations();

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

    public static function setErrorHandler()
    {
        self::$errorHandler = set_error_handler(
            [__CLASS__, 'deprecatedErrorHandler']
        );
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     */
    public static function deprecatedErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if ($errno !== E_USER_DEPRECATED) {
            if (self::$errorHandler) {
                return \call_user_func(self::$errorHandler, $errno, $errstr, $errfile, $errline);
            }

            return true;
        }

        if (!self::$debugbar) {
            return true;
        }

        $backtrace = debug_backtrace(false);

        // Skip current call.
        array_shift($backtrace);

        // Skip vendor libraries and the method where error was triggered.
        while ($current = array_shift($backtrace)) {
            if (isset($current['file']) && strpos($current['file'], 'vendor') !== false) {
                continue;
            }
            if (isset($current['function']) && ($current['function'] === 'user_error' || $current['function'] === 'trigger_error')) {
                $current = array_shift($backtrace);
            }

            break;
        }

        // Add back last call.
        array_unshift($backtrace, $current);

        // Filter arguments.
        foreach ($backtrace as &$current) {
            if (isset($current['args'])) {
                $args = [];
                foreach ($current['args'] as $arg) {
                    if (\is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (\is_bool($arg)) {
                        $args[] = $arg ? 'true' : 'false';
                    } elseif (\is_scalar($arg)) {
                        $args[] = $arg;
                    } elseif (\is_object($arg)) {
                        $args[] = get_class($arg) . ' $object';
                    } elseif (\is_array($arg)) {
                        $args[] = '$array';
                    } else {
                        $args[] = '$object';
                    }
                }
                $current['args'] = $args;
            }
        }
        unset($current);

        self::$deprecations[] = [
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'trace' => $backtrace,
        ];

        // Do not pass forward.
        return true;
    }

    protected static function addDeprecations()
    {
        if (!self::$deprecations) {
            return;
        }

        $collector = new MessagesCollector('deprecated');
        self::addCollector($collector);
        $collector->addMessage('Your site is using following deprecated features:');

        /** @var array $deprecated */
        foreach (self::$deprecations as $deprecated) {
            list($message, $scope) = self::getDepracatedMessage($deprecated);

            $collector->addMessage($message, $scope);
        }
    }

    protected static function getDepracatedMessage($deprecated)
    {
        $scope = 'unknown';
        if (stripos($deprecated['message'], 'grav') !== false) {
            $scope = 'grav';
        } elseif (!isset($deprecated['file'])) {
            $scope = 'unknown';
        } elseif (stripos($deprecated['file'], 'twig') !== false) {
            $scope = 'twig';
        } elseif (stripos($deprecated['file'], 'yaml') !== false) {
            $scope = 'yaml';
        } elseif (stripos($deprecated['file'], 'vendor') !== false) {
            $scope = 'vendor';
        }

        $trace = [];
        foreach ($deprecated['trace'] as $current) {
            $class = isset($current['class']) ? $current['class'] : '';
            $type = isset($current['type']) ? $current['type'] : '';
            $function = static::getFunction($current);
            if (isset($current['file'])) {
                $current['file'] = str_replace(JPATH_ROOT . '/', '', $current['file']);
            }

            unset($current['class'], $current['type'], $current['function'], $current['args']);

            $trace[] = ['call' => $class . $type . $function] + $current;
        }

        return [
            [
                'message' => $deprecated['message'],
                'trace' => $trace
            ],
            $scope
        ];
    }

    protected static function getFunction($trace)
    {
        if (!isset($trace['function'])) {
            return '';
        }

        return $trace['function'] . '(' . implode(', ', $trace['args']) . ')';
    }
}

