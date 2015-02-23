<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Response\HtmlResponse;
use Gantry\Component\Response\Response;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Configurations extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'   => 'index',
            '/**' => 'forward',
        ],
        'POST' => [
            '/**' => 'forward',
        ],
        'PUT'    => [
            '/**' => 'forward'
        ],
        'PATCH'  => [
            '/**' => 'forward'
        ],
        'DELETE' => [
            '/**' => 'forward'
        ]
    ];

    public function index()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $finder = new \Gantry\Component\Config\ConfigFileFinder();
        $files = $finder->getFiles($locator->findResources('gantry-layouts://'), '|\.json$|');
        $files += $finder->getFiles($locator->findResources('gantry-layouts://'));
        $layouts = array_keys($files);
        sort($layouts);

        $layouts_user = array_filter($layouts, function($val) { return strpos($val, 'presets/') !== 0 && substr($val, 0, 1) !== '_'; });
        $layouts_core = array_filter($layouts, function($val) { return strpos($val, 'presets/') !== 0 && substr($val, 0, 1) === '_'; });
        $this->params['layouts'] = ['user' => $layouts_user, 'core' => $layouts_core];

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/configurations.html.twig', $this->params);
    }

    public function forward()
    {
        $path = func_get_args();

        $configurations[] = 'default';
        $configurations += $this->container['configurations']->all()->toArray();

        $configuration = in_array($path[0], $configurations) ? array_shift($path) : 'default';

        $this->container['configuration'] = $configuration;

        $method = $this->params['method'];
        $page = (array_shift($path) ?: 'styles');
        $resource = $this->params['location'] . '/'. $page;

        $this->params['configuration'] = $configuration;
        $this->params['location'] = $resource;
        $this->params['configuration_page'] = $page;
        $this->params['navbar'] = !empty($_GET['navbar']);

        return $this->executeForward($resource, $method, $path, $this->params);
    }

    protected function executeForward($resource, $method = 'GET', $path, $params = [])
    {
        $class = '\\Gantry\\Admin\\Controller\\Html\\' . strtr(ucwords(strtr($resource, '/', ' ')), ' ', '\\');
        if (!class_exists($class)) {
            throw new \RuntimeException('Configuration not found', 404);
        }

        /** @var HtmlController $controller */
        $controller = new $class($this->container);

        // Execute action.
        $response = $controller->execute($method, $path, $params);

        if (!$response instanceof Response) {
            $response = new HtmlResponse($response);
        }

        return $response;
    }
}
