<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Menu\Item;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Gantry;
use Gantry\Framework\Menu as MenuObject;
use Gantry\Framework\Platform;
use RocketTheme\Toolbox\Blueprints\Blueprints;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Menu extends HtmlController
{
    protected $httpVerbs = [
        'GET'    => [
            '/'                  => 'item',
            '/*'                 => 'item',
            '/*/**'              => 'item',
            '/particle'          => 'particle',
            '/particle/*'        => 'validateParticle',
            '/select'            => 'undefined',
            '/select/particle'   => 'selectParticle',
            '/select/module'     => 'selectModule',
            '/edit'              => 'undefined',
            '/edit/*'            => 'edit',
            '/edit/*/**'         => 'editItem',
        ],
        'POST'   => [
            '/'                  => 'save',
            '/*'                 => 'save',
            '/*/**'              => 'item',
            '/particle'          => 'particle',
            '/particle/*'        => 'validateParticle',
            '/select'            => 'undefined',
            '/select/particle'   => 'selectParticle',
            '/select/module'     => 'selectModule',
            '/edit'              => 'undefined',
            '/edit/*'            => 'edit',
            '/edit/*/**'         => 'editItem',
            '/edit/*/validate'   => 'validate',
        ],
        'PUT'    => [
            '/*' => 'replace'
        ],
        'PATCH'  => [
            '/*' => 'update'
        ],
        'DELETE' => [
            '/*' => 'destroy'
        ]
    ];

    public function item($id = null)
    {
        // Load the menu.
        $resource = $this->loadResource($id, !empty($_POST) ? $this->build($_POST) : null);

        // All extra arguments become the path.
        $path = array_slice(func_get_args(), 1);

        // Get menu item and make sure it exists.
        $item = $resource[implode('/', $path)];
        if (!$item) {
            throw new \RuntimeException('Menu item not found', 404);
        }

        // Fill parameters to be passed to the template file.
        $this->params['id'] = $resource->name();
        $this->params['menus'] = $resource->getMenus();
        $this->params['menu'] = $resource;
        $this->params['path'] = implode('/', $path);

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
        if (!empty($_POST['settings'])) {
            $resource->config()->merge(['settings' => json_decode($_POST['settings'], true)]);
        }

        // Fill parameters to be passed to the template file.
        $this->params['id'] = $resource->name();
        $this->params['blueprints'] = $this->loadBlueprints();
        $this->params['data'] = ['settings' => $resource->settings()];

        return $this->container['admin.theme']->render('@gantry-admin//pages/menu/edit.html.twig', $this->params);
    }

    public function save($id = null)
    {
        $resource = $this->loadResource($id);

        $data = $this->build($_POST);

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $filename = $locator->findResource("gantry-config://menu/{$resource->name()}.yaml", true, true);

        $file = YamlFile::instance($filename);
        $file->settings(['inline' => 99]);
        $file->save($data->toArray());
    }

    public function editItem($id)
    {
        // All extra arguments become the path.
        $path = array_slice(func_get_args(), 1);
        $keyword = end($path);

        // Special case: validate instead of fetching menu item.
        if (!empty($_POST) && $keyword == 'validate') {
            $params = array_slice(func_get_args(), 0, -1);
            return call_user_func_array([$this, 'validateitem'], $params);
        }

        $path = implode('/', $path);

        // Load the menu.
        $resource = $this->loadResource($id);

        // Get menu item and make sure it exists.
        /** @var Item $item */
        $item = $resource[$path];
        if (!$item) {
            throw new \RuntimeException('Menu item not found', 404);
        }
        if (!empty($_POST['item'])) {
            $item->update(json_decode($_POST['item'], true));
        }

        // Load blueprints for the menu item.
        $blueprints = $this->loadBlueprints('menuitem');

        $this->params = [
                'id'         => $resource->name(),
                'path'       => $path,
                'blueprints' => ['fields' => $blueprints['form.fields.items.fields']],
                'data'       => $item->toArray() + ['path' => $path],
            ] + $this->params;

        return $this->container['admin.theme']->render('@gantry-admin/pages/menu/menuitem.html.twig', $this->params);
    }

    public function particle()
    {
        /** @var Request $request */
        $request = $this->container['request'];

        $data = $request->get('item');
        if ($data) {
            $data = json_decode($data, true);
        } else {
            $data = $request->getArray();
        }

        $name = isset($data['particle']) ? $data['particle'] : null;

        $block = new BlueprintsForm(CompiledYamlFile::instance("gantry-admin://blueprints/menu/block.yaml")->content());
        $blueprints = new BlueprintsForm($this->container['particles']->get($name));

        // Load particle blueprints and default settings.
        $validator = $this->loadBlueprints('menu');
        $callable = function () use ($validator) {
            return $validator;
        };

        // Create configuration from the defaults.
        $item = new Config($data, $callable);
        $item->def('type', 'particle');
        $item->def('title', $blueprints->get('name'));
        $item->def('options.type', $blueprints->get('type', 'particle'));
        $item->def('options.particle', []);
        $item->def('options.block', []);

        $this->params += [
            'item'          => $item,
            'block'         => $block,
            'data'          => ['particles' => [$name => $item->options['particle']]],
            'particle'      => $blueprints,
            'parent'        => 'settings',
            'prefix'        => "particles.{$name}.",
            'route'         => "configurations.default.settings",
            'action'        => "menu/particle/{$name}"
        ];

        return $this->container['admin.theme']->render('@gantry-admin/pages/menu/particle.html.twig', $this->params);
    }


    public function validateParticle($name)
    {
        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        /** @var Request $request */
        $request = $this->container['request'];

        // Load particle blueprints and default settings.
        $validator = new Blueprints();
        $validator->embed('options', $this->container['particles']->get($name));

        $blueprints = new BlueprintsForm($this->container['particles']->get($name));

        // Create configuration from the defaults.
        $data = new Config([],
            function () use ($validator) {
                return $validator;
            }
        );

        $data->set('type', 'particle');
        $data->set('particle', $name);
        $data->set('title', $request->get('title') ?: $blueprints->get('name'));
        $data->set('options.particle', $request->getArray("particles.{$name}"));
        $data->def('options.particle.enabled', 1);

        $block = $request->getArray('block');
        foreach ($block as $key => $param) {
            if ($param === '') {
                unset($block[$key]);
            }
        }

        $data->join('options.block', $block);

        // TODO: validate

        // Fill parameters to be passed to the template file.
        $this->params['item'] = (object) $data->toArray();

        $html = $this->container['admin.theme']->render('@gantry-admin/menu/item.html.twig', $this->params);

        return new JsonResponse(['item' => $data->toArray(), 'html' => $html]);
    }

    public function selectModule()
    {
        /** @var Platform $platform */
        $platform = $this->container['platform'];

        $this->params['modules'] = $platform->listModules();

        return $this->container['admin.theme']->render('@gantry-admin/menu/module.html.twig', $this->params);
    }

    public function selectParticle()
    {
        $groups = [
            'Particles' => ['particle' => []],
        ];

        $particles = [
            'position'    => [],
            'spacer'      => [],
            'pagecontent' => [],
            'particle' => [],
        ];

        $particles = array_replace($particles, $this->getParticles());
        unset($particles['atom'], $particles['position']);

        foreach ($particles as &$group) {
            asort($group);
        }

        foreach ($groups as $section => $children) {
            foreach ($children as $key => $child) {
                $groups[$section][$key] = $particles[$key];
            }
        }

        $this->params['particles'] = $groups;
        return $this->container['admin.theme']->render('@gantry-admin/menu/particle.html.twig', $this->params);
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

        /** @var Request $request */
        $request = $this->container['request'];

        // Create configuration from the defaults.
        $data = new Config($request->getArray(), $callable);

        // TODO: validate

        return new JsonResponse(['settings' => (array) $data->get('settings')]);
    }

    public function validateitem($id)
    {
        // All extra arguments become the path.
        $path = array_slice(func_get_args(), 1);

        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        // Load the menu.
        $resource = $this->loadResource($id);

        // Load particle blueprints and default settings.
        $validator = $this->loadBlueprints('menuitem');
        $callable = function () use ($validator) {
            return $validator;
        };

        /** @var Request $request */
        $request = $this->container['request'];

        // Create configuration from the defaults.
        $data = new Config($request->getArray(), $callable);

        // TODO: validate

        $item = $resource[implode('/', $path)];
        $item->update($data->toArray());

        // Fill parameters to be passed to the template file.
        $this->params['id'] = $resource->name();
        $this->params['item'] = $item;
        $this->params['group'] = isset($group) ? $group : $resource[implode('/', array_slice($path, 0, 2))]->group;

        $html = $this->container['admin.theme']->render('@gantry-admin/menu/item.html.twig', $this->params);

        return new JsonResponse(['path' => implode('/', $path), 'item' => $data->toArray(), 'html' => $html]);
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
     * @param Config $config
     *
     * @return MenuObject
     * @throws \RuntimeException
     */
    protected function loadResource($id, Config $config = null)
    {
        /** @var MenuObject $menus */
        $menus = $this->container['menu'];

        return $menus->instance(['menu' => $id], $config);
    }

    /**
     * Load blueprints.
     *
     * @param string $name
     *
     * @return BlueprintsForm
     */
    protected function loadBlueprints($name = 'menu')
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $filename = $locator("gantry-admin://blueprints/menu/{$name}.yaml");
        return new BlueprintsForm(CompiledYamlFile::instance($filename)->content());
    }


    public function build($raw)
    {
        $settings = isset($raw['settings']) ? json_decode($raw['settings'], true) : [];
        $order = isset($raw['ordering']) ? json_decode($raw['ordering'], true) : null;
        $items = isset($raw['items']) ? json_decode($raw['items'], true) : null;

        if (!is_array($order) || !is_array($items)) {
            throw new \RuntimeException('Invalid menu structure', 400);
        }

        krsort($order);
        $ordering = [];
        foreach ($order as $path => $columns) {
            foreach ($columns as $column => $colitems) {
                $list = [];
                foreach ($colitems as $item) {
                    $name = trim(substr($item, strlen($path)), '/');
                    if (isset($ordering[$item])) {
                        $list[$name] = $ordering[$item];
                        unset($ordering[$item]);
                    } else {
                        $list[$name] = '';
                    }
                }
                if (count($columns) > 1) {
                    $ordering[$path][$column] = $list;
                } else {
                    $ordering[$path] = $list;
                }
            }
        }

        $data = new Config([]);
        $data->set('settings', $settings);
        $data->set('ordering', $ordering['']);
        $data->set('items', $items);

        return $data;
    }

    protected function getParticles()
    {
        $particles = $this->container['particles']->all();

        $list = [];
        foreach ($particles as $name => $particle) {
            $type = isset($particle['type']) ? $particle['type'] : 'particle';
            $particleName = isset($particle['name']) ? $particle['name'] : $name;
            $list[$type][$name] = $particleName;
        }

        return $list;
    }
}
