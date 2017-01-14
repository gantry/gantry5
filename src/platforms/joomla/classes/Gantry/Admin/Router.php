<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Router\Router as BaseRouter;
use Gantry\Joomla\StyleHelper;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Gantry administration router for Joomla.
 */
class Router extends BaseRouter
{
    public function boot()
    {
        \JHtml::_('behavior.keepalive');

        $app = \JFactory::getApplication();
        $input = $app->input;

        // TODO: Remove style variable.
        $style = $input->getInt('style');
        $theme = $input->getCmd('theme');
        $path = array_filter(explode('/', $input->getString('view', '')), function($var) { return $var !== ''; });

        $this->setTheme($theme, $style);

        /** @var Request $request */
        $request = $this->container['request'];

        $this->method = $request->getMethod();
        $this->path = $path ?: (isset($this->container['theme.name']) ? ['configurations', true] : ['themes']);
        $this->resource = array_shift($this->path);
        $this->format = $input->getCmd('format', 'html');
        $ajax = ($this->format == 'json');

        $this->params = [
            'user' => \JFactory::getUser(),
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'format' => $this->format,
            'params' => $request->post->getJsonArray('params')
        ];

        return $this;
    }

    public function setTheme($theme, $style)
    {
        if ($style) {
            \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
            $table = \JTable::getInstance('Style', 'TemplatesTable');
            $table->load(['id' => $style, 'client_id' => 0]);

            $theme = $table->template;
        }
        if (!$theme) {
            $theme = StyleHelper::getDefaultStyle()->template;
        }

        $path = JPATH_SITE . '/templates/' . $theme;

        if (!is_file("{$path}/gantry/theme.yaml")) {
            $theme = null;
            $this->container['streams']->register();

            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];

            CompiledYamlFile::$defaultCachePath = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
            CompiledYamlFile::$defaultCaching = $this->container['global']->get('compile_yaml', 1);
        }

        $this->container['base_url'] = \JUri::base(true) . '/index.php?option=com_gantry5';

        $this->container['ajax_suffix'] = '&format=json';

        $token = \JSession::getFormToken();

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
        $lang = \JFactory::getLanguage();
        $lang->load($languageFile, JPATH_SITE)
            || $lang->load($languageFile, $path)
            || $lang->load($languageFile, $path, 'en-GB');

        return $this;
    }

    protected function checkSecurityToken()
    {
        return \JSession::checkToken('get');
    }

    /**
     * Send response to the client.
     *
     * @param Response $response
     * @return string
     */
    protected function send(Response $response)
    {
        $app = \JFactory::getApplication();
        $document = \JFactory::getDocument();
        $document->setCharset($response->charset);
        $document->setMimeEncoding($response->mimeType);

        // Output HTTP header.
        $app->setHeader('Status', $response->getStatus());
        $app->setHeader('Content-Type', $response->mimeType . '; charset=' . $response->charset);
        foreach ($response->getHeaders() as $key => $values) {
            $replace = true;
            foreach ($values as $value) {
                $app->setHeader($key, $value, $replace);
                $replace = false;
            }
        }

        if ($response instanceof JsonResponse) {
            $app->setHeader('Expires', 'Wed, 17 Aug 2005 00:00:00 GMT', true);
            $app->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
            $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
            $app->setHeader('Pragma', 'no-cache');
            $app->sendHeaders();
        }

        // Output Gantry response.
        echo $response;

        if ($response instanceof JsonResponse) {
            $app->close();
        }
    }
}
