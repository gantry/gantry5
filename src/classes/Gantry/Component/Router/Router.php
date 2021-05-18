<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
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
use Gantry\Component\Filesystem\Streams;
use Gantry\Component\Response\HtmlResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Gantry;
use Gantry\Framework\Services\ErrorServiceProvider;
use Psr\Http\Message\ResponseInterface;
use RocketTheme\Toolbox\Event\EventDispatcher;
use Whoops\Exception\ErrorException;

/**
 * Class Router
 * @package Gantry\Component\Router
 */
abstract class Router implements RouterInterface
{
    /** @var Gantry */
    protected $container;
    /** @var string */
    protected $format;
    /** @var string */
    protected $resource;
    /** @var string */
    protected $method;
    /** @var array */
    protected $path;
    /** @var array */
    protected $params;

    /**
     * Router constructor.
     * @param Gantry $container
     */
    public function __construct(Gantry $container)
    {
        $this->container = $container;
    }

    /**
     * @return ResponseInterface|bool
     * @throws ErrorException
     */
    public function dispatch()
    {
        $this->boot();
        $this->load();

        // Render the page or execute the task.
        try {
            $response = $this->execute($this->resource, $this->method, $this->path, $this->params, $this->format);

        } catch (ErrorException $e) {
            throw $e;

        } catch (\Exception $e) {
            // Handle errors.
            if ($this->container->debug()) {
                throw $e;
            }
            $response = $this->getErrorResponse($e, $this->format === 'json');
        }

        return $this->send($response);
    }

    /**
     * @param string $resource
     * @param string $method
     * @param array $path
     * @param array $params
     * @param string $format
     * @return HtmlResponse|JsonResponse|Response
     */
    public function execute($resource, $method = 'GET', $path = [], $params = [], $format = 'html')
    {
        $class = '\\Gantry\\Admin\\Controller\\' . ucfirst($format) . '\\' . str_replace(' ', '\\', ucwords(str_replace('/', ' ', $resource)));
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

    /**
     * @return mixed
     */
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

        if (isset($this->container['theme.path'])) {
            $className = $this->container['theme.path'] . '/custom/includes/gantry.php';
            if (!is_file($className)) {
                $className = $this->container['theme.path'] . '/includes/gantry.php';
            }
            if (is_file($className)) {
                include $className;
            }
        }

        if (isset($this->container['theme'])) {
            // Initialize current theme if it is set.
            /** @phpstan-ignore-next-line */
            $this->container['theme'];
        } else {
            // Otherwise initialize streams and error handler manually.
            /** @var Streams $streams */
            $streams = $this->container['streams'];
            $streams->register();
            $this->container->register(new ErrorServiceProvider);
        }

        $this->container['admin.theme'] = static function () {
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
        /** @phpstan-ignore-next-line */
        $this->container['admin.theme'];

        return $this;
    }

    /**
     * @param \Exception $e
     * @param bool $json
     * @return HtmlResponse|JsonResponse
     */
    protected function getErrorResponse(\Exception $e, $json = false)
    {
        $response = new HtmlResponse;
        $response->setStatusCode($e->getCode());

        $params = [
            'ajax' => $json,
            'title' => $response->getStatus(),
            'error' => $e,
        ];

        /** @var Theme $theme */
        $theme = $this->container['admin.theme'];
        $response->setContent($theme->render('@gantry-admin/error.html.twig', $params));

        if ($json) {
            return new JsonResponse([$e, $response]);
        }

        return $response;
    }

    /**
     * @param Response $response
     * @return ResponseInterface|bool
     */
    protected function send(Response $response)
    {
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
