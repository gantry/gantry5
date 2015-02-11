<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Gantry;
use Gantry\Framework\Menu as MenuObject;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Menu extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'             => 'item',
            '/*'            => 'item',
            '/*/**'         => 'item',
            '/edit'         => 'undefined',
            '/edit/*'       => 'edit',
            '/edit/*/**'    => 'menuitem',
        ],
        'POST' => [
            '/'             => 'save',
            '/*'            => 'save',
            '/*/**'         => 'save',
            '/edit'         => 'undefined',
            '/edit/*'       => 'undefined',
            '/edit/*/validate' => 'validate',
            '/edit/*/**'    => 'validateitem',
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

    public function item($id = 'mainmenu')
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

        // Fill parameters to be passed to the template file.
        $this->params['id'] = $id;
        $this->params['menus'] = $resource->getMenus();
        $this->params['menu'] = $resource;
        $this->params['path'] = implode('/', $path);
        $this->params['menu_settings'] = ['settings' => $resource->config()->get('settings')];

        // Detect special case to fetch only single column group.
        $group = isset($_GET['group']) ? intval($_GET['group']) : null;

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

            $this->params['item'] = $item;
            $this->params['group'] = isset($group) ? $group : $resource[implode('/', array_slice($path, 0, 2))]->group;

            return $this->container['admin.theme']->render('@gantry-admin/menu/' . $layout . '.html.twig', $this->params) ?: '&nbsp;';
        }
    }

    public function edit($id)
    {
        // Load the menu.
        $resource = $this->loadResource($id);

        // Fill parameters to be passed to the template file.
        $this->params['id'] = $id;
        $this->params['blueprints'] = $this->loadBlueprints();
        $this->params['data'] = $resource->config();

        return $this->container['admin.theme']->render('@gantry-admin//pages/menu/edit.html.twig', $this->params);
    }

    public function save($id = 'mainmenu')
    {
        $order = isset($_POST['ordering']) ? json_decode($_POST['ordering'], true) : null;
        $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : null;

        if (!$order || !$items) {
            throw new \RuntimeException('Error while saving menu: Invalid structure', 400);
        }

        $data = new Config([]);

        foreach ($order as $path => $columns) {
            $has_columns = count($columns) > 1;
            foreach ($columns as $column => $colitems) {
                $column = $has_columns ? $column : '';
                foreach ($colitems as $item) {
                    $item = substr($item, strlen($path));
                    $data->set(preg_replace('|[\./]+|', '.', "ordering.{$path}.{$column}.{$item}"), []);
                }
            }
        }

        $data->set('items', $items);

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $filename = $locator->findResource("gantry-config://menu/{$id}.yaml", true, true);

        $file = YamlFile::instance($filename);
        $file->settings(['inline' => 99]);
        $file->save($data->toArray());
    }

    public function menuitem($id)
    {
        // All extra arguments become the path.
        $path = array_slice(func_get_args(), 1);
        $path = implode('/', $path);

        // Load the menu.
        $resource = $this->loadResource($id);

        // Get menu item and make sure it exists.
        $item = $resource[$path];
        if (!$item) {
            throw new \RuntimeException('Menu item not found', 404);
        }

        // Load blueprints for the menu item.
        $blueprints = $this->loadBlueprints('menuitem');

        $this->params = [
                'id' => $id,
                'path' => $path,
                'blueprints' => ['fields' => $blueprints['form.fields.items.fields']],
                'data' => $item->toArray() + ['path' => $path],
            ] + $this->params;

        return $this->container['admin.theme']->render('@gantry-admin/pages/menu/menuitem.html.twig', $this->params);
    }

    public function validate($id)
    {
        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        // Load particle blueprints and default settings.
        $validator = $this->loadBlueprints('menu');
        $callable = function () use ($validator) {
            return $validator;
        };

        // Create configuration from the defaults.
        $data = new Config($_POST, $callable);

        // TODO: validate

        return new JsonResponse(['data' => $data->toArray()]);
    }

    public function validateitem($id)
    {
        // All extra arguments become the path.
        $path = array_slice(func_get_args(), 1);
        $keyword = array_pop($path);

        // Validate only exists for JSON.
        if ($keyword != 'validate' || empty($this->params['ajax'])) {
            $this->undefined();
        }

        $path = implode('/', $path);

        // Load particle blueprints and default settings.
        $validator = $this->loadBlueprints('menuitem');
        $callable = function () use ($validator) {
            return $validator;
        };

        // Create configuration from the defaults.
        $data = new Config($_POST, $callable);

        // TODO: validate

        return new JsonResponse(['path' => $path, 'data' => $data->toArray()]);
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
     * @return BlueprintsForm
     */
    protected function loadBlueprints($name = 'menu')
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $filename = $locator("gantry-admin://blueprints/menu/{$name}.yaml");
        return new BlueprintsForm(CompiledYamlFile::instance($filename)->content());
    }
}
