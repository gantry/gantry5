<?php
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
        if ($locator->schemeExists('gantry-admin')) {
            $error_page->addResourcePath($locator('gantry-admin://css'));
        } else {
            $error_page->addResourcePath($locator('gantry-theme://css'));
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
