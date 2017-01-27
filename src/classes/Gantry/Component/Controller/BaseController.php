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

namespace Gantry\Component\Controller;

use Gantry\Framework\Request;
use RocketTheme\Toolbox\DI\Container;
use RuntimeException;

abstract class BaseController implements RestfulControllerInterface
{
    /**
     * @var string Default HTTP method.
     */
    protected $method = 'GET';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array List of HTTP verbs and their actions.
     */
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

    /**
     * @var array Parameters from router.
     */
    protected $params = [];

    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $container['request'];
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
        $this->method = $method;
        $this->setParams($params);
        list($action, $path) = $this->resolveHttpVerb($method, $path);

        if (!method_exists($this, $action)) {
            $action = 'undefined';
        }

        return call_user_func_array([$this, $action], $path);
    }

    /**
     * Set router parameters. Replaces the old parameters.
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @example GET /resources
     *
     * @return mixed
     */
    public function index()
    {
        return $this->undefined();
    }

    /**
     * @example GET /resources/:id
     *
     * @param string $id
     * @return mixed
     */
    public function display($id)
    {
        return $this->undefined();
    }

    /**
     * Special sub-resource to create a new resource (returns a form).
     *
     * @example GET /resources/create
     *
     * @return mixed
     */
    public function create()
    {
        return $this->undefined();
    }

    /**
     * Special sub-resource to edit existing resource (returns a form).
     *
     * @example GET /resources/:id/edit
     *
     * @param string $id
     * @return mixed
     */
    public function edit($id)
    {
        return $this->undefined();
    }

    /**
     * @example POST /resources
     *
     * @return mixed
     */
    public function store()
    {
        return $this->undefined();
    }

    /**
     * @example PUT /resources/:id
     *
     * @param string $id
     * @return mixed
     */
    public function replace($id)
    {
        return $this->undefined();
    }

    /**
     * @example PATCH /resources/:id
     *
     * @param string $id
     * @return mixed
     */
    public function update($id)
    {
        return $this->undefined();
    }

    /**
     * @example DELETE /resources/:id
     *
     * @param string $id
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->undefined();
    }

    /**
     * Catch all action for all undefined actions.
     *
     * @return mixed
     * @throws RuntimeException
     */
    public function undefined()
    {
        if (in_array($this->method, ['HEAD', 'GET'])) {
            throw new RuntimeException('Page Not Found', 404);
        }

        throw new RuntimeException('Invalid Action', 405);
    }

    /**
     * Catch all action for forbidden actions.
     *
     * @return mixed
     * @throws RuntimeException
     */
    public function forbidden()
    {
        throw new RuntimeException('Forbidden', 403);
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
        while (($current = array_shift($remaining)) !== null) {
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
