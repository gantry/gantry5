<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Admin;

use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Router\Router as BaseRouter;
use Grav\Common\Grav;
use Grav\Common\Uri;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Router extends BaseRouter
{
    public function boot()
    {
        $grav = Grav::instance();

        /** @var \Grav\Plugin\Admin $admin */
        $admin = $grav['admin'];

        /** @var Uri $uri */
        $uri = $grav['uri'];

        $parts = array_filter(explode('/', $admin->route), function($var) { return $var !== ''; });

        // Set theme.
        $theme = array_shift($parts);
        $this->setTheme($theme);

        /** @var Request $request */
        $request = $this->container['request'];

        // Figure out the action we want to make.
        $this->method = $request->getMethod();
        $this->path = $parts;
        $this->resource = $theme ? (array_shift($this->path) ?: 'about') : 'themes';
        $this->format = $uri->extension('html');
        $ajax = ($this->format == 'json');

        $this->params = [
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'format' => $this->format,
            'params' => $request->post->getJsonArray('params')
        ];

        $this->container['base_url'] = $grav['gantry5_plugin']->base;

        $this->container['ajax_suffix'] = '.json';

        $this->container['routes'] = [
            '1' => '/%s',
            'themes' => '',

            'picker/layouts' => '/layouts',
        ];
    }

    public function setTheme($theme)
    {
        $grav = Grav::instance();
        if (!$theme) {
            $theme = $grav['config']->get('system.theme');
        }

        $path = "themes://{$theme}";

        if (!is_file("{$path}/gantry/theme.yaml")) {
            $theme = null;
            $this->container['streams']->register();

            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];
            $this->container['file.yaml.cache.path'] = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
        }

        $plugin = $grav['gantry5_plugin'];

        $this->container['base_url'] = $plugin->base;

        if (!$theme) {
            return $this;
        }

        $this->container['theme.path'] = $path;
        $this->container['theme.name'] = $theme;

        // TODO: Load language file for the template.

        return $this;
    }

    protected function checkSecurityToken()
    {
        // TODO: add CSRF check.
        return true;
    }

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

        if ($response instanceof JsonResponse) {
            exit();
        }

        return true;
    }
}
