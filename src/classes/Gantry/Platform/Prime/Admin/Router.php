<?php
namespace Gantry\Admin;

use Gantry\Component\Request\Request;
use Gantry\Component\Router\Router as BaseRouter;

class Router extends BaseRouter
{
    public function boot()
    {
        $request = new Request();

        // Split normalized request path to its parts.
        $parts = explode('/', PAGE_PATH);

        if (isset($parts[0]) && $parts[0] == 'admin') {
            // We are inside admin; we can skip the first part.
            array_shift($parts);

            // Second parameter is the resource.
            $this->resource = array_shift($parts) ?: 'overview';
            $style = isset($this->container['theme.name']) ? $this->container['theme.name'] : '';

        } else {
            // We are not inside admin; first parameter is the resource.
            $this->resource = array_shift($parts) ?: 'themes';
            $style = '';
        }

        // Figure out the action we want to make.
        $httpMethod = $request->getMethod();
        $this->method = isset($this->httpMethods[$httpMethod]) ? $this->httpMethods[$httpMethod] : null;
        $this->path = $parts;
        $this->format = PAGE_EXTENSION;
        $ajax = ($this->format == 'json');

        // FIXME:
        $this->method = 'GET';

        $this->params = [
            'id'   => 0,
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'params' => isset($_POST['params']) && is_string($_POST['params']) ? json_decode($_POST['params'], true) : []
        ];

        if ($style) {
            $this->container['theme.id'] = 0;
            $this->container['theme.path'] = PRIME_ROOT . '/themes/' . $style;
            $this->container['theme.name'] = $style;
            $this->container['theme.title'] = ucfirst($style);
            $this->container['theme.params'] = [];
        }

        $this->container['base_url'] = rtrim(PRIME_URI, '/') . "/{$style}/admin";

        $this->container['ajax_suffix'] = '.json';

        $this->container['routes'] = [
            '1' => '/%s',
            'themes' => '',

            'picker/layouts' => '/layouts',
            'picker/particles' => '/particles'
        ];
    }
}
