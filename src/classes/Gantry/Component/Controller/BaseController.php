<?php
namespace Gantry\Component\Controller;

use Gantry\Admin\Router;
use RocketTheme\Toolbox\DI\Container;
use RuntimeException;

abstract class BaseController implements RestfulControllerInterface
{
    protected $method = 'GET';
    protected $httpVerbs = [
        'GET' => [
            '/'         => 'index',
            '/create'   => 'create',
            '/*'        => 'display',
            '/*/edit'   => 'edit'
        ],
        'POST' => [
            '/'  => 'store'
        ],
        'PUT' => [
            '/*' => 'replace'
        ],
        'PATCH' => [
            '/*' => 'update'
        ],
        'DELETE' => [
            '/*' => 'destroy'
        ]
    ];
    protected $params;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Execute controller.
     *
     * @param string $method
     * @param array $path
     * @param array $params
     * @return mixed
     * @throws \RuntimeException
     */
    public function execute($method, array $path, array $params)
    {
        $this->params = $params;
        list($action, $path) = $this->resolveHttpVerb($method, $path);

        if (!method_exists($this, $action)) {
            $action = 'undefined';
        }

        return call_user_func_array([$this, $action], $path);
    }

    public function index()
    {
        return $this->undefined();
    }

    public function display($id)
    {
        return $this->undefined();
    }

    public function create()
    {
        return $this->undefined();
    }

    public function edit($id)
    {
        return $this->undefined();
    }

    public function store()
    {
        return $this->undefined();
    }

    public function replace($id)
    {
        return $this->undefined();
    }

    public function update($id)
    {
        return $this->undefined();
    }

    public function destroy($id)
    {
        return $this->undefined();
    }

    /**
     * Catch all action for all undefined actions.
     *
     * @param array $path
     * @return mixed
     * @throws \RuntimeException
     */
    public function undefined()
    {
        if (in_array($this->method, ['HEAD', 'GET'])) {
            throw new RuntimeException('Page Not Found', 404);
        }

        throw new RuntimeException('Invalid Action', 405);
    }

    /**
     * Load resource.
     *
     * Function throws an exception if resource does not exist.
     *
     * @param string|int $id
     * @throws \RuntimeException
     */
    protected function loadResource($id)
    {
        throw new RuntimeException('Resource Not Found', 404);
    }

    /**
     * Resolve HTTP verb.
     *
     * @param string $method
     * @param array $items
     * @return array [function, parameters]
     */
    protected function resolveHttpVerb($method, array $items)
    {
        // HEAD has identical behavior to GET.
        $method = ($method == 'HEAD') ? 'GET' : $method;

        if (!isset($this->httpVerbs[$method])) {
            // HTTP method is not defined.
            return ['undefined', $items];
        }

        $path = '';
        $remaining = $items;
        $variables = [];
        $actions = $this->httpVerbs[$method];

        // Build path for the verb and fetch all the variables.
        while ($current = array_shift($remaining)) {
            $test = "{$path}/{$current}";

            if (!isset($actions[$test])) {
                // Specific path not found, check if we have a variable.
                $test = "{$path}/*";

                if (isset($actions[$test])) {
                    // Variable found, save the value and move on.
                    $variables[] = $current;

                } elseif (isset($actions[$test . '*'])) {
                    // Wildcard found, pass through rest of the variables.
                    $path = $test . '*';
                    $variables = array_merge($variables, [$current], $remaining);
                    break;

                } else {
                    // No matches; we are done here.
                    return ['undefined', $items];
                }
            }

            // Path was found.
            $path = $test;
        }

        // No matching path; check if we have verb for the root.
        if (!$path && isset($actions['/'])) {
            $path = '/';
        }

        // Get the action.
        $action = $path ? $actions[$path] : 'undefined';

        return [$action, $variables];
    }
}
