<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Whoops;

/**
 * Class SystemFacade
 * @package Gantry\Component\Whoops
 */
class SystemFacade extends \Whoops\Util\SystemFacade
{
    /** @var array */
    protected $registeredPatterns;
    /** @var callable */
    protected $whoopsErrorHandler;
    /** @var callable */
    protected $whoopsExceptionHandler;
    /** @var callable */
    protected $whoopsShutdownHandler;
    /** @var callable|null */
    protected $platformExceptionHandler;

    /**
     * @param  array|string $patterns List or a single regex pattern to match for silencing errors in particular files.
     */
    public function __construct($patterns = [])
    {
        $this->registeredPatterns = array_map(
            static function ($pattern) {
                return ['pattern' => $pattern];
            },
            (array) $patterns
        );
    }

    /**
     * @param callable $handler
     * @param int|string $types
     *
     * @return callable|null
     */
    public function setErrorHandler(callable $handler, $types = 'use-php-defaults')
    {
        $this->whoopsErrorHandler = $handler;

        return parent::setErrorHandler([$this, 'handleError'], $types);
    }

    /**
     * @param callable $function
     *
     * @return void
     */
    public function registerShutdownFunction(callable $function)
    {
        $this->whoopsShutdownHandler = $function;
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * @param callable $handler
     *
     * @return callable|null
     */
    public function setExceptionHandler(callable $handler)
    {
        $this->whoopsExceptionHandler = $handler;
        $this->platformExceptionHandler = parent::setExceptionHandler([$this, 'handleException']);

        return $this->platformExceptionHandler;
    }

    /**
     * Converts generic PHP errors to \ErrorException instances, before passing them off to be handled.
     *
     * This method MUST be compatible with set_error_handler.
     *
     * @param int    $level
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @return bool
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = null, $line = null)
    {
        // TODO: remove when upgrading to Twig 2+
        if (($level === E_DEPRECATED) && strpos($file, '/twig/') !== false) {
            if (str_contains($message, '#[\ReturnTypeWillChange]') || str_contains($message, 'Passing null to parameter')) {
                return true;
            }
        }

        $handler = $this->whoopsErrorHandler;

        if (!$this->registeredPatterns) {
            // Just forward to parent function is there aren't no registered patterns.
            return $handler($level, $message, $file, $line);

        }

        // If there are registered patterns, only handle errors if error matches one of the patterns.
        if ($level & error_reporting()) {
            foreach ($this->registeredPatterns as $entry) {
                $pathMatches = $file && preg_match($entry['pattern'], $file);
                if ($pathMatches) {
                    return $handler($level, (string)$message, $file, $line);
                }
            }
        }

        // Propagate error to the next handler, allows error_get_last() to work on silenced errors.
        return false;
    }

    /**
     * Handles an exception, ultimately generating a Whoops error page.
     *
     * @param  \Throwable $exception
     * @return void
     */
    public function handleException($exception)
    {
        $handler = $this->whoopsExceptionHandler;

        // If there are registered patterns, only handle errors if error matches one of the patterns.
        if ($this->registeredPatterns) {
            foreach ($this->registeredPatterns as $entry) {
                $file = $exception->getFile();
                $pathMatches = $file && preg_match($entry['pattern'], $file);
                if ($pathMatches) {
                    $handler($exception);
                    return;
                }
            }
        }

        // Propagate error to the next handler.
        if ($this->platformExceptionHandler) {
            call_user_func_array($this->platformExceptionHandler, [&$exception]);
        }
    }

    /**
     * Special case to deal with Fatal errors and the like.
     */
    public function handleShutdown()
    {
        $handler = $this->whoopsShutdownHandler;

        $error = $this->getLastError();

        // Ignore core warnings and errors.
        if ($error && !($error['type'] & (E_CORE_WARNING | E_CORE_ERROR))) {
            $handler();
        }
    }
}
