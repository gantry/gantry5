<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\Config;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Gantry;
use Gantry\Framework\Menu as MenuObject;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Menu extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'             => 'index',
            '/*'            => 'item',
            '/*/**'         => 'item',
        ],
        'POST' => [
            '/'             => 'store',
        ],
        'PUT' => [
            '/*' => 'replace'
        ],
        'PATCH' => [
            '/*' => 'update'
        ],
        'DELETE' => [
            '/*' => 'destroy'
        ]
    ];

    public function index()
    {
        return $this->item('main-menu');
    }

    public function item($id)
    {
        $path = func_get_args();

        $last = end($path);
        $group = (string) intval($last) === (string) $last ? array_pop($path) : null;

        try {
            $resource = $this->loadResource($id);
            array_shift($path);
        } catch (\Exception $e) {
            // Continue for now...
            $id = 'main-menu';
            $resource = $this->loadResource($id);
        }

        $menuItem = implode('/', $path);
        $item = $resource[$menuItem];
        if (!$resource[$menuItem]) {
            throw new \RuntimeException('Menu item not found', 404);
        }

        $this->params['id'] = 'menu';
        $this->params['prefix'] = 'particles.menu.';
        $this->params['route'] = 'settings';
        $this->params['blueprints'] = $this->loadBlueprints();
        $this->params['menu'] = $resource;
        $this->params['item'] = $item;

        /** @var MenuObject $menu */
        $menu = $this->container['menu'];

        /** @var Config $config */
        $config = $this->container['config'];

        $this->params['particle'] = $config->get('particles.instances.menu.' . $id);

        $config->joinDefaults('particles.menu.items', $menu->instance($config->get('particles.menu'))->getMenuItems());

        if (empty($this->params['ajax']) || empty($_GET['inline'])) {
            if (count($path) > 0) {
                $this->params['columns'] = $resource[$path[0]];
            }
            if (count($path) > 1) {
                $this->params['column'] = isset($group) ? $group : $resource[implode('/', array_slice($path, 0, 2))]->group;
                $this->params['override'] = $item;
            }

            return $this->container['admin.theme']->render('@gantry-admin//pages/menu/menu.html.twig', $this->params);
        } else {
            // Get layout name.
            $layout = $this->layoutName(count($path) + (int) isset($group));
            $this->params['group'] = isset($group) ? $group : $resource[implode('/', array_slice($path, 0, 2))]->group;

            return $this->container['admin.theme']->render('@gantry-admin/menu/' . $layout . '.html.twig', $this->params);
        }
    }

    protected function layoutName($level)
    {
        switch ($level) {
            case 0:
                return 'base';
            case 1:
                return 'columns';
            default:
                return 'list';
        }
    }

    /**
     * Load resource.
     *
     * @param string $id
     * @return Config
     */
    protected function loadResource($id)
    {
        /** @var MenuObject $menus */
        $menus = $this->container['menu'];

        /** @var Config $config */
        $config = $this->container['config'];

        if ($id) {
            $params = $config->get('particles.instances.menu.' . $id);
        } else {
            $params = $config->get('particles.menu');
        }

        if (!$params) {
            throw new \RuntimeException('Resource not found', 404);
        }

        return $menus->instance($params);
    }

    /**
     * Load blueprints.
     *
     * @return object
     */
    protected function loadBlueprints()
    {
        return $this->container['particles']->get('menu');
    }
}
