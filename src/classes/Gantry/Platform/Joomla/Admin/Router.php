<?php
namespace Gantry\Admin;

use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Component\Router\Router as BaseRouter;
use Joomla\Registry\Registry;

class Router extends BaseRouter
{
    public function boot()
    {
        $app = \JFactory::getApplication();
        $input = $app->input;

        $this->method = 'GET';
        $this->path = explode('/', $input->getString('view'));
        $this->resource = array_shift($this->path) ?: 'themes';
        $this->format = $input->getCmd('format', 'html');

        \JHtml::_('behavior.keepalive');

        $this->params = [
            'id'   => $input->getInt('id'),
            'ajax' => ($this->format == 'json'),
            'location' => $this->resource,
            'params' => isset($_POST['params']) && is_string($_POST['params']) ? json_decode($_POST['params']) : []
        ];

        // If style is set, resolve the template and load it.
        $style = $input->getInt('style', 0);
        if ($style) {
            \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
            $table = \JTable::getInstance('Style', 'TemplatesTable');
            $table->load($style);

            $this->container['theme.id'] = $table->id;
            $this->container['theme.path'] = JPATH_SITE . '/templates/' . $table->template;
            $this->container['theme.name'] = $table->template;
            $this->container['theme.title'] = $table->title;
            $this->container['theme.params'] = (new Registry($table->params))->toArray();
        }

        $this->container['base_url'] = \JUri::base(true) . '/index.php?option=com_gantryadmin';

        $this->container['ajax_suffix'] = '&format=json';

        $this->container['routes'] = [
            '1' => '&view=%s&style=' . $style,

            'picker/layouts' => '&view=layouts&style=' . $style,
            'picker/particles' => '&view=particles&style=' . $style
        ];
    }

    protected function send(Response $response)
    {
        // Output HTTP header.
        $app = \JFactory::getApplication();
        $document = \JFactory::getDocument();
        $document->setCharset($response->charset);
        $document->setMimeEncoding($response->mimeType);

        header("HTTP/1.1 {$response->getStatus()}", true, $response->getStatusCode());
        header("Content-Type: {$response->mimeType}; charset={$response->charset}");
        foreach ($response->getHeaders() as $key => $values) {
            $replace = true;
            foreach ($values as $value) {
                $app->setHeader($key, $value, $replace);
                $replace = false;
            }
        }
        echo $response;

        if ($response instanceof JsonResponse) {
            // It is much faster and safer to exit now than to let Joomla to send the response.
            $app->sendHeaders();
            $app->close();
        }
    }
}
