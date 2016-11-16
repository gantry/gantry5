<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Admin;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Router\Router as BaseRouter;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Common\Utils;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Router extends BaseRouter
{
    public function boot()
    {
        $grav = Grav::instance();
        $plugin = $grav['gantry5_plugin'];

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
        if (!$theme) {
            $this->resource = array_shift($this->path) ?: 'themes';
        } else {
            if (!$this->path) {
                $this->path = ['configurations', 'styles'];
            }
            $this->resource = array_shift($this->path);
        }
        $this->format = $uri->extension('html');
        $ajax = ($this->format == 'json');

        $this->params = [
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'format' => $this->format,
            'params' => $request->post->getJsonArray('params')
        ];

        $this->container['base_url'] = $plugin->base;

        $this->container['ajax_suffix'] = '.json';

        $nonce = Utils::getNonce('gantry-admin');
        $this->container['routes'] = [
            '1' => '/%s?nonce=' . $nonce,
            'themes' => '',
            'picker/layouts' => '/layouts?nonce=' . $nonce,
        ];

        $this->container['ajax_nonce'] = $nonce;
    }

    public function setTheme($theme)
    {
        $grav = Grav::instance();
        $plugin = $grav['gantry5_plugin'];

        $this->container['base_url'] = $plugin->base;

        if (!$theme) {
            $theme = $grav['config']->get('system.theme');
        }

        $path = "themes://{$theme}";

        if (!$theme || !is_file("{$path}/gantry/theme.yaml") || !is_file("{$path}/theme.php")) {
            $theme = null;
            $this->container['streams']->register();

            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];

            CompiledYamlFile::$defaultCachePath = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
            CompiledYamlFile::$defaultCaching = $this->container['global']->get('compile_yaml', 1);
        }

        return $this;
    }

    protected function checkSecurityToken()
    {
        /** @var Request $request */
        $request = $this->container['request'];
        $nonce = $request->get->get('nonce');
        return isset($nonce) && Utils::verifyNonce($nonce, 'gantry-admin');
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

        if ($response instanceof JsonResponse) {
            header('Expires: Wed, 17 Aug 2005 00:00:00 GMT', true);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT', true);
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
        }

        echo $response;

        if ($response instanceof JsonResponse) {
            exit();
        }

        return true;
    }
}
