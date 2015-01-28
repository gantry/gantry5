<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\Blueprints;
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
            '/edit'         => 'undefined',
            '/edit/*'       => 'undefined',
            '/edit/*/**'    => 'form',
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
        // Index points to default menu.
        return $this->item('mainmenu');
    }

    public function item($id)
    {
        // All extra arguments become the path.
        $path = array_slice(func_get_args(), 1);

        // Detect special case to fetch only one column group (last part of the path was index number).
        $last = end($path);
        $group = (string) intval($last) === (string) $last ? array_pop($path) : null;

        // Load the menu.
        $resource = $this->loadResource($id);

        // Get menu item and make sure it exists.
        $item = $resource[implode('/', $path)];
        if (!$item) {
            throw new \RuntimeException('Menu item not found', 404);
        }

        // Fill parameters to be passed to the template file.
        $this->params['id'] = 'menu';
        $this->params['prefix'] = 'particles.menu.' . $id;
        $this->params['route'] = 'particles.menu';
        $this->params['blueprints'] = $this->loadBlueprints();
        $this->params['menu'] = $resource;
        $this->params['item'] = $item;

        /** @var Config $config */
        $config = $this->container['config'];

        $this->params['particle'] = $config->get('menu.' . $id);

        if (empty($this->params['ajax']) || empty($_GET['inline'])) {
            // Handle special case to fetch only one column group.
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

    public function form($id)
    {
        // All extra arguments become the path.
        $path = array_slice(func_get_args(), 1);

        // Load the menu.
        $resource = $this->loadResource($id);

        // Get menu item and make sure it exists.
        $item = $resource[implode('/', $path)];
        if (!$item) {
            throw new \RuntimeException('Menu item not found', 404);
        }
        // Load blueprints for the menu item.
        $blueprints = $this->loadBlueprints('menuitem');

        // Get the prefix.
        $prefix = "menu.{$id}." . implode('.', $path);

        $this->params = [
                'path' => implode('/', $path),
                'prefix' => $prefix,
                'blueprints' => ['fields' => $blueprints['form.fields.items.fields']],
                'data' =>  $this->container['config']->get($prefix),
            ] + $this->params;

        return $this->container['admin.theme']->render('@gantry-admin/pages/menu/settings.html.twig', $this->params);
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
     * @return MenuObject
     * @throws \RuntimeException
     */
    protected function loadResource($id)
    {
        /** @var MenuObject $menus */
        $menus = $this->container['menu'];

        return $menus->instance(['config' => ['menu' => $id]]);
    }

    /**
     * Load blueprints.
     *
     * @param string $name
     * @return Blueprints
     */
    protected function loadBlueprints($name = 'menu')
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $filename = $locator("gantry-admin://blueprints/menu/{$name}.yaml");
        return new Blueprints(CompiledYamlFile::instance($filename)->content());
    }
}
