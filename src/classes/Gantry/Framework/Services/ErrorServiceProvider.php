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

namespace Gantry\Framework\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Run;

class ErrorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $container['locator'];

        // Setup Whoops-based error handler
        $errors = new Run;

        $error_page = new PrettyPageHandler;
        $error_page->setPageTitle('Crikey! There was an error...');
        $error_page->setEditor('sublime');
        foreach ($locator->findResources('gantry-assets://css/whoops.css') as $path) {
            $error_page->addResourcePath(dirname($path));
     }
        $error_page->addCustomCss('whoops.css');

        $json_page = new JsonResponseHandler;
        $json_page->onlyForAjaxRequests(true);

        $errors->pushHandler($error_page, 'pretty');
        $errors->pushHandler(new PlainTextHandler, 'text');
        $errors->pushHandler($json_page, 'json');

        $errors->register();

        $container['errors'] = $errors;
    }
}
