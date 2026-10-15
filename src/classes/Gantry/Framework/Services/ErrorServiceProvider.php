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
     * @param Container $container
     */
    public function register(Container $container)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $container['locator'];

        /** @var Platform $platform */
        $platform = $container['platform'];

        // Setup Whoops-based error handler
        $system = new SystemFacade($platform->errorHandlerPaths());
        $errors = new Run($system);

        $error_page = new PrettyPageHandler();
        $error_page->setPageTitle('Crikey! There was an error...');
        $error_page->setEditor('sublime');
        foreach ($locator->findResources('gantry-assets://css/whoops.css') as $path) {
            $error_page->addResourcePath(dirname($path));
        }
        $error_page->addCustomCss('whoops.css');

        // Mask secrets (database credentials, keys, salts, tokens) in the environment tables.
        $secretPattern = '/(PASS|PWD|SECRET|KEY|TOKEN|SALT|AUTH|CREDENTIAL|DB_|DATABASE|PRIVATE)/i';
        foreach (['_SERVER' => $_SERVER, '_ENV' => $_ENV, '_COOKIE' => $_COOKIE] as $global => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach (array_keys($values) as $key) {
                if ($global === '_COOKIE' || preg_match($secretPattern, (string) $key)) {
                    $error_page->hideSuperglobalKey($global, $key);
                }
            }
        }
        foreach (array_keys((array) getenv()) as $key) {
            if (preg_match($secretPattern, (string) $key)) {
                $error_page->hideSuperglobalKey('_ENV', $key);
                $error_page->hideSuperglobalKey('_SERVER', $key);
            }
        }

        $errors->pushHandler($error_page);

        $jsonRequest = $this->format === 'json' || ($_SERVER && isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] === 'application/json');
        if (Misc::isAjaxRequest() || $jsonRequest) {
            $json_handler = new JsonResponseHandler();
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
