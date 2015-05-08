<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Router\Router as BaseRouter;
use Joomla\Registry\Registry;

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

        /** @var Request $request */
        $request = $this->container['request'];

        $this->method = $request->getMethod();
        $this->path = explode('/', $input->getString('view'));
        $this->resource = array_shift($this->path) ?: 'themes';
        $this->format = $input->getCmd('format', 'html');
        $ajax = ($this->format == 'json');

        $this->params = [
            'user' => \JFactory::getUser(),
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'format' => $this->format,
            'params' => isset($_POST['params']) && is_string($_POST['params']) ? json_decode($_POST['params'], true) : []
        ];

        // If style is set, resolve the template and load it.
        $style = $input->getInt('style', 0);
        if ($style) {
            \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
            $table = \JTable::getInstance('Style', 'TemplatesTable');
            $table->load($style);

            $template = $table->template;
            $path = JPATH_SITE . '/templates/' . $template;

            $this->container['theme.path'] = $path;
            $this->container['theme.name'] = $template;

            // Load language file for the template.
            $languageFile = 'tpl_' . $template;
            $lang = \JFactory::getLanguage();
            $lang->load($languageFile, JPATH_SITE)
                || $lang->load($languageFile, $path)
                || $lang->load($languageFile, $path, 'en-GB');
        }

        $this->container['base_url'] = \JUri::base(true) . '/index.php?option=com_gantry5';

        $this->container['ajax_suffix'] = '&format=json';

        $token = \JSession::getFormToken();

        $this->container['routes'] = [
            '1' => "&view=%s&style={$style}&{$token}=1",

            'themes' => '&view=themes',
            'picker/layouts' => "&view=layouts&style={$style}&{$token}=1",
            'picker/particles' => "&view=particles&style={$style}&{$token}=1"
        ];
    }

    protected function checkSecurityToken()
    {
        return \JSession::checkToken('get');
    }

    /**
     * Send response to the client.
     *
     * @param Response $response
     */
    protected function send(Response $response)
    {
        $app = \JFactory::getApplication();
        $document = \JFactory::getDocument();
        $document->setCharset($response->charset);
        $document->setMimeEncoding($response->mimeType);

        // Output HTTP header.
        header("HTTP/1.1 {$response->getStatus()}", true, $response->getStatusCode());
        header("Content-Type: {$response->mimeType}; charset={$response->charset}");
        foreach ($response->getHeaders() as $key => $values) {
            $replace = true;
            foreach ($values as $value) {
                $app->setHeader($key, $value, $replace);
                $replace = false;
            }
        }

        // Output Gantry response.
        echo $response;

        if ($response instanceof JsonResponse) {
            // It is much faster and safer to exit now than to let Joomla to send the response.
            $app->sendHeaders();
            $app->close();
        }
    }
}
