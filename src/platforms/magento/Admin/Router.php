<?php
namespace Gantry\Admin;

use Gantry\Component\Request\Request;
use Gantry\Component\Router\Router as BaseRouter;
use Gantry\Framework\Base\Gantry;

class Router extends BaseRouter
{
    public function boot()
    {
        $this->resource = 'themes';
        $this->method = 'GET';
        // TODO: get path from request object
        $this->path = [];
        $this->format = 'html';
        $ajax = ($this->format == 'json');

        $this->params = [
            'id'   => 0,
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'params' => $request->post->getJsonArray('params')
        ];
/*
        if ($style) {
            $this->container['theme.path'] = PRIME_ROOT . '/themes/' . $style;
            $this->container['theme.name'] = $style;
        }
*/
//        $this->container['base_url'] = rtrim(PRIME_URI, '/') . "/{$style}/admin";

        $this->container['ajax_suffix'] = '.json';

        $this->container['routes'] = [
            '1' => '/%s',
            'themes' => '',

            'picker/layouts' => '/layouts',
        ];
    }
}
