<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Services;

use Gantry\Component\Whoops\SystemFacade;
use Gantry\Debugger;
use Gantry\Framework\Platform;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\Util\Misc;

/**
 * Class ErrorServiceProvider
 * @package Gantry\Framework\Services
 */
class ErrorServiceProvider implements ServiceProviderInterface
{
    /** @var string */
    protected $format;

    /**
     * ErrorServiceProvider constructor.
     * @param string $format
     */
    public function __construct($format = 'html')
    {
        $this->format = $format;
    }

    /**
     * Detailed error pages are only allowed for logged-in administrators.
     *
     * @return bool
     */
    public static function allowed()
    {
        return \function_exists('is_user_logged_in') && \function_exists('current_user_can')
            && \is_user_logged_in() && \current_user_can('manage_options');
    }

    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        // Defense in depth: never register the detailed handler for anyone but administrators.
        if (!static::allowed()) {
            return;
        }

        /** @var UniformResourceLocator $locator */
        $locator = $container['locator'];

        /** @var Platform $platform */
        $platform = $container['platform'];

        // Setup Whoops-based error handler
        $system = new SystemFacade($platform->errorHandlerPaths());
        $errors = new Run($system);

        $error_page = new PrettyPageHandler();
        $error_page->setPageTitle('Crikey! There was an error...');
        foreach ($locator->findResources('gantry-assets://css/whoops.css') as $path) {
            $error_page->addResourcePath(dirname($path));
        }
        $error_page->addCustomCss('whoops.css');

        // Scrub sensitive details: hide every superglobal value except a small safe list,
        // all cookies, sessions, POST/FILES and the whole environment.
        $safeServerKeys = ['REQUEST_METHOD', 'REQUEST_URI', 'QUERY_STRING', 'HTTP_HOST', 'HTTP_USER_AGENT', 'HTTP_ACCEPT', 'REQUEST_TIME', 'REQUEST_TIME_FLOAT', 'SERVER_PROTOCOL'];
        // phpcs:disable WordPress.Security.NonceVerification -- only array keys are read, to hide them.
        $globals = [
            '_SERVER' => isset($_SERVER) ? $_SERVER : [],
            '_ENV' => isset($_ENV) ? $_ENV : [],
            '_COOKIE' => isset($_COOKIE) ? $_COOKIE : [],
            '_SESSION' => isset($_SESSION) ? $_SESSION : [],
            '_POST' => isset($_POST) ? $_POST : [],
            '_FILES' => isset($_FILES) ? $_FILES : [],
        ];
        // phpcs:enable WordPress.Security.NonceVerification
        foreach ($globals as $global => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach (array_keys($values) as $key) {
                if ($global !== '_SERVER' || !in_array($key, $safeServerKeys, true)) {
                    $error_page->hideSuperglobalKey($global, $key);
                }
            }
        }
        foreach (array_keys((array) getenv()) as $key) {
            $error_page->hideSuperglobalKey('_ENV', $key);
            if (!in_array($key, $safeServerKeys, true)) {
                $error_page->hideSuperglobalKey('_SERVER', $key);
            }
        }
        // Do not expose the local editor link handler (reveals absolute file paths as URLs).
        $error_page->setEditor(static function () {
            return false;
        });

        $errors->pushHandler($error_page);

        $jsonRequest = $this->format === 'json' || ($_SERVER && isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] === 'application/json');
        if (Misc::isAjaxRequest() || $jsonRequest) {
            $json_handler = new JsonResponseHandler();
            $json_handler->addTraceToOutput(false);
            //$json_handler->setJsonApi(true);

            $errors->pushHandler($json_handler);
        }

        $errors->register();

        $container['errors'] = $errors;

        if (\GANTRY_DEBUGGER) {
            Debugger::setErrorHandler();
        }
    }
}
