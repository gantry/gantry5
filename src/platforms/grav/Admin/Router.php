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
use Gantry\Component\Router\Router as BaseRouter;
use Grav\Common\Grav;
use Grav\Common\Uri;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Router extends BaseRouter
{
    public function boot()
    {
        $grav = Grav::instance();

        /** @var Uri $uri */
        $uri = $grav['uri'];

        /** @var \Grav\Plugin\Admin $admin */
        $admin = $grav['admin'];

        /** @var Request $request */
        $request = $this->container['request'];

        $parts = array_filter(explode('/', $admin->route), function($var) { return $var !== ''; });

        // Set theme.
        $theme = array_shift($parts);
        $this->setTheme($theme);

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
            'params' => $request->post->getJsonArray('params')
        ];

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

        // TODO: set base url
        $this->container['base_url'] = '';

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
}
