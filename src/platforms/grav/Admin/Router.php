<?php
namespace Gantry\Admin;

use Gantry\Component\Request\Request;
use Gantry\Component\Router\Router as BaseRouter;
use Grav\Common\GPM\Remote\Grav;
use Grav\Common\Uri;

class Router extends BaseRouter
{
    public function boot()
    {
        $grav = Grav::getGrav();

        /** @var Uri $uri */
        $uri = $grav['uri'];

        /** @var \Grav\Plugin\Admin $admin */
        $admin = $grav['admin'];

        /** @var Request $request */
        $request = $this->container['request'];

        $theme = isset($this->container['theme.name']) ? $this->container['theme.name'] : '';

        if ($theme) {
            $this->container['theme.path'] = GRAV_ROOT . '/themes/' . $theme;
            $this->container['theme.name'] = $theme;
        }

        $this->load();

        $parts = array_filter(explode('/', $admin->route), function($var) { return $var !== ''; });

        // Second parameter is the resource.
        $this->resource = array_shift($parts) ?: 'about';

        // Figure out the action we want to make.
        $this->method = $request->getMethod();
        $this->path = $parts;
        $this->format = $uri->extension('html');
        $ajax = ($this->format == 'json');

        $this->params = [
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'format' => $this->format,
            'params' => $request->post->getJsonArray('params');
        ];

        $this->container['ajax_suffix'] = '.json';

        $this->container['routes'] = [
            '1' => '/%s',
            'themes' => '',

            'picker/layouts' => '/layouts',
        ];
    }
}
