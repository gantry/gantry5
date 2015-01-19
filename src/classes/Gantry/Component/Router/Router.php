<?php
namespace Gantry\Component\Router;

use Gantry\Component\Controller\BaseController;
use Gantry\Component\Response\HtmlResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Router\RouterInterface;
use RocketTheme\Toolbox\DI\Container;

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

        } catch (\Exception $e) {
            // Handle errors.
            $response = $this->getErrorResponse($e, $this->format == 'json');
        }

        $this->send($response);
    }

    public function execute($resource, $method = 'GET', $path, $params = [], $format = 'html')
    {
        $class = '\\Gantry\\Admin\\Controller\\' . ucfirst($format) . '\\' . ucfirst($resource);

        if (!class_exists($class)) {
            if ($format == 'json') {
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

    abstract protected function boot();

    protected function load()
    {
        if (isset($this->container['theme.path']) && file_exists($this->container['theme.path'] . '/includes/gantry.php')) {
            include $this->container['theme.path'] . '/includes/gantry.php';
        }

        $this->container['admin.theme'] = function () {
            return new \Gantry\Admin\Theme\Theme(GANTRYADMIN_PATH);
        };

        // Boot the service.
        $this->container['admin.theme'];
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
    }
}
