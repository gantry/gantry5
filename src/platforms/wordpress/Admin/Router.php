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

/**
 * Gantry administration router for WordPress.
 */
class Router extends BaseRouter
{
    public function boot()
    {
        /** @var Request $request */
        $request = $this->container['request'];

        $this->method = $request->getMethod();
        $this->path = explode('/', isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'about');
        $this->resource = array_shift($this->path) ?: 'themes';
        $this->format = isset( $_GET['format'] ) ? sanitize_key( $_GET['format'] ) : 'html';
        $ajax = ($this->format == 'json');

        $this->params = [
            'ajax' => $ajax,
            'location' => $this->resource,
            'method' => $this->method,
            'format' => $this->format,
            'params' => isset($_POST['params']) && is_string($_POST['params']) ? json_decode($_POST['params'], true) : []
        ];

        $this->setTemplate();

        return $this;
    }

    public function setTemplate()
    {
        $this->container['base_url'] = \admin_url( 'themes.php?page=layout-manager' );

        $this->container['ajax_suffix'] = '&format=json';

        // FIXME: Add missing security token.
        $token = 'token';

        $this->container['routes'] = [
            '1' => "&view=%s&style={$style}&{$token}=1",

            'themes' => '&view=themes',
            'picker/layouts' => "&view=layouts&style={$style}&{$token}=1",
        ];

        return $this;
    }

    protected function checkSecurityToken()
    {
        // FIXME: Check security token and return false on failure.
        return true;
    }

    /**
     * Send response to the client.
     *
     * @param Response $response
     */
    protected function send(Response $response)
    {
/*
        $app = \JFactory::getApplication();
        $document = \JFactory::getDocument();
        $document->setCharset($response->charset);
        $document->setMimeEncoding($response->mimeType);
*/
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

        if ($response instanceof JsonResponse) {
            // Output Gantry response.
            echo $response;

            // It is much faster and safer to exit now than to let Joomla to send the response.
            //$app->sendHeaders();
            //$app->close();
        }

        return $response;
    }
}
