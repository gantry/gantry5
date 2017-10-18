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

namespace Gantry\Component\Router;

use Gantry\Admin\EventListener;
use Gantry\Admin\Theme;
use Gantry\Component\Controller\BaseController;
use Gantry\Component\Response\HtmlResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Services\ErrorServiceProvider;
use RocketTheme\Toolbox\DI\Container;
use RocketTheme\Toolbox\Event\EventDispatcher;
use Whoops\Exception\ErrorException;

abstract class Router implements RouterInterface
{
    /**
     * @var Container
     */
    protected $container;

    protected $format;
    protected $resource;
    protected $method;
    protected $path;
    protected $params;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function dispatch()
    {
        $this->boot();

        $this->load();

        // Render the page or execute the task.
        try {
            $response = static::execute($this->resource, $this->method, $this->path, $this->params, $this->format);

        } catch (ErrorException $e) {
            throw $e;

        } catch (\Exception $e) {
            // Handle errors.
            if ($this->container->debug()) {
                throw $e;
            }
            $response = $this->getErrorResponse($e, $this->format == 'json');
        }

        return $this->send($response);
    }

    public function execute($resource, $method = 'GET', $path, $params = [], $format = 'html')
    {
        $class = '\\Gantry\\Admin\\Controller\\' . ucfirst($format) . '\\' . strtr(ucwords(strtr($resource, '/', ' ')), ' ', '\\');

        // Protect against CSRF Attacks.
        if (!in_array($method, ['GET', 'HEAD'], true) && !$this->checkSecurityToken()) {
            throw new \RuntimeException('Invalid security token; please reload the page and try again.', 403);
        }

        if (!class_exists($class)) {
            if ($format === 'json') {
                // Special case: All HTML requests can be returned also as JSON.
                $response = $this->execute($resource, $method, $path, $params, 'html');
                return $response instanceof JsonResponse ? $response : new JsonResponse($response);
            }

            throw new \RuntimeException('Page Not Found', 404);
        }

        /** @var BaseController $controller */
        $controller = new $class($this->container);

        // Execute action.
        $response = $controller->execute($method, $path, $params);

        if (!$response instanceof Response) {
            $response = new HtmlResponse($response);
        }

        return $response;
    }

    /**
     * @return $this
     */
    abstract protected function boot();

    abstract protected function checkSecurityToken();

    /**
     * @return $this
     */
    public function load()
    {
        static $loaded = false;

        if ($loaded) {
            return $this;
        }

        $loaded = true;

        if (isset($this->container['theme.path']) && file_exists($this->container['theme.path'] . '/includes/gantry.php')) {
            include $this->container['theme.path'] . '/includes/gantry.php';
        }

        if (isset($this->container['theme'])) {
            // Initialize current theme if it is set.
            $this->container['theme'];
        } else {
            // Otherwise initialize streams and error handler manually.
            $this->container['streams']->register();
            $this->container->register(new ErrorServiceProvider);
        }

        $this->container['admin.theme'] = function () {
            return new Theme(GANTRYADMIN_PATH);
        };

        // Add event listener.
        if (class_exists('Gantry\\Admin\\EventListener')) {
            $listener = new EventListener;

            /** @var EventDispatcher $events */
            $events = $this->container['events'];
            $events->addSubscriber($listener);
        }

        // Boot the service.
        $this->container['admin.theme'];

        return $this;
    }

    protected function getErrorResponse(\Exception $e, $json = false)
    {
        $response = new HtmlResponse;
        $response->setStatusCode($e->getCode());

        $params = [
            'ajax' => $json,
            'title' => $response->getStatus(),
            'error' => $e,
        ];
        $response->setContent($this->container['admin.theme']->render('@gantry-admin/error.html.twig', $params));

        if ($json) {
            return new JsonResponse([$e, $response]);
        }

        return $response;
    }

    protected function send(Response $response) {
        // Output HTTP header.
        header("HTTP/1.1 {$response->getStatus()}", true, $response->getStatusCode());
        header("Content-Type: {$response->mimeType}; charset={$response->charset}");
        foreach ($response->getHeaders() as $key => $values) {
            foreach ($values as $value) {
                header("{$key}: {$value}");
            }
        }

        echo $response;

        return true;
    }
}
