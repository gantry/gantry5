<?php
namespace Gantry\Admin;

use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Router\RouterInterface;
use RocketTheme\Toolbox\DI\Container;

class Router implements RouterInterface
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function dispatch()
    {
        $path = explode('/', PAGE_PATH);

        if (isset($path[0]) && $path[0] == 'admin') {
            array_shift($path);

            $view = array_shift($path) ?: 'overview';
            $layout = array_shift($path) ?: 'index';
            $style = isset($this->container['theme.name']) ? $this->container['theme.name'] : '';

        } else {
            $view = array_shift($path) ?: 'themes';
            $layout = array_shift($path) ?: 'index';
            $style = '';
        }

        $format = PAGE_EXTENSION;

        $params = [
            'id'   => 0,
            'ajax' => $format == 'json',
            'location' => $view,
            'path' => $path,
            'params' => isset($_POST['params']) && is_string($_POST['params']) ? json_decode($_POST['params'], true) : []
        ];

        // If style is set, resolve the template and load it.
        if ($style) {
            $this->container['theme.id'] = 0;
            $this->container['theme.path'] = $path = PRIME_ROOT . '/themes/' . $style;
            $this->container['theme.name'] = $style;
            $this->container['theme.title'] = ucfirst($style);
            $this->container['theme.params'] = [];

            if (file_exists($path . '/includes/gantry.php')) {
                include $path . '/includes/gantry.php';
            }
        }

        $this->container['admin.theme'] = function () {
            return new \Gantry\Admin\Theme\Theme(GANTRYADMIN_PATH);
        };

        // Boot the service.
        $this->container['admin.theme'];
        $this->container['base_url'] = rtrim(PRIME_URI, '/') . "/{$style}/admin";

        $this->container['routes'] = [
            'ajax' => '/{view}/{method}.json',
            'themes' => '',
            'overview' => '/overview',
            'presets' => '/presets',
            'settings' => '/settings',
            'menu' => '/menu',
            'pages' => '/pages',
            'pages/edit' => '/pages/edit',
            'pages/create' => '/pages/create',
            'assignments' => '/assignments',
        ];

        $class = '\\Gantry\\Admin\\Controller\\' . ucfirst($format) . '\\' . ucfirst($view);

        // Render the page.
        $contents = '';
        try {
            if (!class_exists($class) || !method_exists($class, $layout)) {

                $params = [
                    'title' => '404 Not Found',
                    'error' => new \RuntimeException('Page Not Found ' . $class, 404)
                ];
                $contents = $this->container['admin.theme']->render('@gantry-admin/error.html.twig', $params);
            } else {

                $controller = new $class($this->container);
                $contents = $controller->{$layout}($params);
            }

        } catch (\Exception $e) {
            if ($format == 'json') {
                $contents = new JsonResponse($e);

            } else {
                if (class_exists('\Tracy\Debugger') && \Tracy\Debugger::isEnabled() && !\Tracy\Debugger::$productionMode )  {
                    // We have Tracy enabled; will display and/or log error with it.
                    throw $e;
                }

                exit(($e->getCode() ?: 500) . ' ' . $e->getMessage());
            }
        }

        if ($contents instanceof JsonResponse) {
            // Tell the browser that our response is in JSON.
            header('Content-Type: application/json', true, $contents->getResponseCode());

            echo $contents;

            exit();
        }

        echo $contents;
    }
}
