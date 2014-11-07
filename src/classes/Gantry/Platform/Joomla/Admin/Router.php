<?php
namespace Gantry\Admin;

use Gantry\Component\Response\Json;
use Gantry\Component\Router\RouterInterface;
use RocketTheme\Toolbox\DI\Container;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

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
        $this->container['theme.path'] = JPATH_SITE . '/templates/gantry';

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
            'overview' => '&view=overview',
            'settings' => '&view=settings',
            'pages' => '&view=pages',
            'pages/edit' => '&view=pages&layout=edit',
            'pages/create' => '&view=pages&layout=create',
            'assignments' => '&view=assignments',
            'updates' => '&view=updates',
        ];

        $input = \JFactory::getApplication()->input;
        $view = $input->getCmd('view', 'themes');
        $layout = $input->getCmd('layout', 'index');
        $format = $input->getCmd('format', 'html');

        // Render the page.
        $contents = '';
        try {
            $class = '\\Gantry\\Admin\\Controller\\' . ucfirst($format) . '\\' . ucfirst($view);

            if (class_exists($class) && method_exists($class, $layout)) {
                $controller = new $class($this->container);
                $contents = (string) $controller->{$layout}();
            } else {
                throw new \RuntimeException('Not Found', 404);
            }

        } catch (\Exception $e) {
            if ($format == 'json') {
                $contents = new Json($e);
            } else {
                if (class_exists('\Tracy\Debugger') && \Tracy\Debugger::isEnabled() && !\Tracy\Debugger::$productionMode )  {
                    // We have Tracy enabled; will display and/or log error with it.
                    throw $e;
                }

                \JError::raiseError($e->getCode() ?: 500, $e->getMessage());
            }
        }

        echo $contents;
    }
}
