<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Config\Config;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Streams;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Router\Router as BaseRouter;
use Gantry\Joomla\StyleHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Gantry administration router for Joomla.
 */
class Router extends BaseRouter
{
    /**
     * @return $this
     */
    public function boot()
    {
        HTMLHelper::_('behavior.keepalive');

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $input = $application->input;

        // TODO: Remove style variable.
        $style = $input->getInt('style');
        $theme = $input->getCmd('theme');
        $path = array_filter(explode('/', $input->getString('view', '')), static function($var) { return $var !== ''; });

        $this->setTheme($theme, $style);

        /** @var Request $request */
        $request = $this->container['request'];

        $this->method = $request->getMethod();
        $this->path = $path ?: (isset($this->container['theme.name']) ? ['configurations', true] : ['themes']);
        $this->resource = array_shift($this->path);
        $this->format = strtolower($input->getCmd('format', 'html'));
        $ajax = ($this->format === 'json');

        $this->params = [
            'user' => $application->getIdentity(),
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'format' => $this->format,
            'params' => $request->post->getJsonArray('params')
        ];

        return $this;
    }

    /**
     * @param string $theme
     * @param string $style
     * @return $this
     */
    public function setTheme($theme, $style)
    {
        if ($style) {
            $theme = StyleHelper::getStyle($style)->template;
        }
        if (!$theme) {
            $theme = StyleHelper::getDefaultStyle()->template;
        }

        $path = JPATH_SITE . '/templates/' . $theme;

        if (!is_file("{$path}/gantry/theme.yaml")) {
            $theme = '';
            /** @var Streams $streams */
            $streams = $this->container['streams'];
            $streams->register();

            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];

            /** @var Config $global */
            $global = $this->container['global'];

            CompiledYamlFile::$defaultCachePath = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
            CompiledYamlFile::$defaultCaching = $global->get('compile_yaml', 1);
        }

        $this->container['base_url'] = Uri::base(true) . '/index.php?option=com_gantry5';

        $this->container['ajax_suffix'] = '&format=json';

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $session = $application->getSession();
        $token = $session::getFormToken();

        $this->container['routes'] = [
            '1' => "&view=%s&theme={$theme}&{$token}=1",

            'themes' => '&view=themes',
            'picker/layouts' => "&view=layouts&theme={$theme}&{$token}=1",
        ];

        if (!$theme) {
            return $this;
        }

        $this->container['theme.path'] = $path;
        $this->container['theme.name'] = $theme;

        // Load language file for the template.
        $languageFile = 'tpl_' . $theme;

        $language = $application->getLanguage();
        $language->load($languageFile, JPATH_SITE)
            || $language->load($languageFile, $path)
            || $language->load($languageFile, $path, 'en-GB');

        return $this;
    }

    /**
     * @return bool
     */
    protected function checkSecurityToken()
    {
        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $session = $application->getSession();

        return $session::checkToken('get');
    }

    /**
     * Send response to the client.
     *
     * @param Response $response
     */
    protected function send(Response $response)
    {
        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $document = $application->getDocument();
        $document->setCharset($response->charset);
        $document->setMimeEncoding($response->mimeType);

        // Output HTTP header.
        $application->setHeader('Status', $response->getStatus());
        $application->setHeader('Content-Type', $response->mimeType . '; charset=' . $response->charset);
        foreach ($response->getHeaders() as $key => $values) {
            $replace = true;
            foreach ($values as $value) {
                $application->setHeader($key, $value, $replace);
                $replace = false;
            }
        }

        if ($response instanceof JsonResponse) {
            $application->setHeader('Expires', 'Wed, 17 Aug 2005 00:00:00 GMT', true);
            $application->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
            $application->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
            $application->setHeader('Pragma', 'no-cache');
            $application->sendHeaders();
        }

        // Output Gantry response.
        echo $response;

        if ($response instanceof JsonResponse) {
            $application->close();
        }
    }
}
