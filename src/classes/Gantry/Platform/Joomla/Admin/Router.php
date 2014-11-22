<?php
namespace Gantry\Admin;

use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Router\RouterInterface;
use RocketTheme\Toolbox\DI\Container;

class Router implements RouterInterface
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function dispatch()
    {
        $app = \JFactory::getApplication();
        $input = $app->input;
        $format = $input->getCmd('format', 'html');
        $view = $input->getCmd('view', 'themes');
        $layout = $input->getCmd('layout', 'index');
        $style = $input->getInt('style', 0);

        $params = [
            'id'   => $input->getInt('id'),
            'ajax' => ($format == 'json'),
            'location' => $view
        ];

        // If style is set, resolve the template and load it.
        if ($style) {
            \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
            $table = \JTable::getInstance('Style', 'TemplatesTable');
            $table->load($style);

            $this->container['theme.id'] = $table->id;
            $this->container['theme.path'] = $path = JPATH_SITE . '/templates/' . $table->template;
            $this->container['theme.name'] = $table->template;
            $this->container['theme.title'] = $table->title;
            $this->container['theme.params'] = (new \JRegistry($table->params))->toArray();

            if (file_exists($path . '/includes/gantry.php')) {
                include $path . '/includes/gantry.php';
            }
        }

        // Define Gantry Admin services.
        $this->container['admin.config'] = function () {
            return \Gantry\Framework\Config::instance(
                JPATH_CACHE . '/gantry/config.php',
                GANTRYADMIN_PATH
            );
        };
        $this->container['admin.theme'] = function () {
            return new \Gantry\Admin\Theme\Theme(GANTRYADMIN_PATH);
        };

        // Boot the service.
        $this->container['admin.theme'];
        $this->container['base_url'] = \JUri::base(false) . 'index.php?option=com_gantryadmin';


        $this->container['routes'] = [
            'themes' => '',
            'overview' => '&view=overview&style=' . $style,
            'settings' => '&view=settings&style=' . $style,
            'pages' => '&view=pages&style=' . $style,
            'pages/edit' => '&view=pages&layout=edit&style=' . $style,
            'pages/create' => '&view=pages&layout=create&style=' . $style,
            'assignments' => '&view=assignments&style=' . $style,
            'updates' => '&view=updates&style=' . $style,
        ];

        $class = '\\Gantry\\Admin\\Controller\\' . ucfirst($format) . '\\' . ucfirst($view);

        // Render the page.
        $contents = '';
        try {
            if (!class_exists($class) || !method_exists($class, $layout)) {
                throw new \RuntimeException('Not Found', 404);
            }

            $controller = new $class($this->container);
            $contents = $controller->{$layout}($params);

        } catch (\Exception $e) {
            if ($format == 'json') {
                $contents = new JsonResponse($e);

            } else {
                if (class_exists('\Tracy\Debugger') && \Tracy\Debugger::isEnabled() && !\Tracy\Debugger::$productionMode )  {
                    // We have Tracy enabled; will display and/or log error with it.
                    throw $e;
                }

                \JError::raiseError($e->getCode() ?: 500, $e->getMessage());
            }
        }

        if ($contents instanceof JsonResponse) {
            // Tell the browser that our response is in JSON.
            header('Content-Type: application/json', true, $contents->getResponseCode());

            echo $contents;

            // It's much faster and safer to exit now than let Joomla to send the response.
            \JFactory::getApplication()->close();
        }

        echo $contents;
    }
}
