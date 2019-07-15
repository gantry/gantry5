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

namespace Gantry\Admin;

use Gantry\Component\Request\Request;
use Gantry\Component\Router\Router as BaseRouter;

class Router extends BaseRouter
{
    public function boot()
    {
        /** @var Request $request */
        $request = $this->container['request'];

        // Split normalized request path to its parts.
        $parts = array_filter(explode('/', PAGE_PATH), function($var) { return $var !== ''; });

        $theme = isset($this->container['theme.name']) ? $this->container['theme.name'] : '';

        if ($theme) {
            $this->container['theme.path'] = PRIME_ROOT . '/themes/' . $theme;
            $this->container['theme.name'] = $theme;
        }

        $this->load();

        if ($theme && isset($parts[0]) && $parts[0] == 'admin') {
            // We are inside admin; we can skip the first part.
            array_shift($parts);

            // Second parameter is the resource.
            $this->resource = array_shift($parts) ?: 'about';

        } else {
            // We are not inside admin or style doesn't exist; fall back to theme listing.
            $theme = '';
            $parts = [];
            $this->resource = 'themes';
        }

        // Figure out the action we want to make.
        $this->method = $request->getMethod();
        $this->path = $parts;
        $this->format = PAGE_EXTENSION;
        $ajax = ($this->format == 'json');

        $this->params = [
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'format' => $this->format,
            'params' => $request->post->getJsonArray('params')
        ];

        $this->container['base_url'] = rtrim(PRIME_URI, '/') . "/{$theme}/admin";

        $this->container['ajax_suffix'] = '.json';

        $this->container['routes'] = [
            '1' => '/%s',
            'themes' => '',

            'picker/layouts' => '/layouts',
        ];
    }

    protected function checkSecurityToken()
    {
        // TODO: add CSRF check.
        return true;
    }
}
