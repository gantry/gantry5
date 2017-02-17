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

namespace Gantry\Framework\Services;

use Gantry\Component\Whoops\SystemFacade;
use Gantry\Framework\Platform;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\Util\Misc;

class ErrorServiceProvider implements ServiceProviderInterface
{
    protected $format;

    public function __construct($format = 'html')
    {
        $this->format = $format;
    }

    public function register(Container $container)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $container['locator'];

        /** @var Platform $platform */
        $platform = $container['platform'];

        // Setup Whoops-based error handler
        $system = new SystemFacade($platform->errorHandlerPaths());
        $errors = new Run($system);

        $error_page = new PrettyPageHandler;
        $error_page->setPageTitle('Crikey! There was an error...');
        $error_page->setEditor('sublime');
        foreach ($locator->findResources('gantry-assets://css/whoops.css') as $path) {
            $error_page->addResourcePath(dirname($path));
        }
        $error_page->addCustomCss('whoops.css');

        $errors->pushHandler($error_page);

        $jsonRequest = $this->format === 'json' || ($_SERVER && isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/json');
        if (Misc::isAjaxRequest() || $jsonRequest) {
            $json_handler = new JsonResponseHandler;
            //$json_handler->setJsonApi(true);

            $errors->pushHandler($json_handler);
        }

        $errors->register();

        $container['errors'] = $errors;
    }
}
