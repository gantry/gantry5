<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Admin\HtmlController;
use Gantry\Component\Config\BlueprintSchema;
use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Menu\Item;
use Gantry\Component\Request\Input;
use Gantry\Component\Response\HtmlResponse;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Framework\Menu as MenuObject;
use RocketTheme\Toolbox\Event\Event;
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
            '/select/widget'     => 'selectWidget',
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
            '/select/widget'     => 'selectWidget',
            '/widget'            => 'widget',
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

    public function execute($method, array $path, array $params)
    {
        if (!$this->authorize('menu.manage')) {
            $this->forbidden();
        }

        return parent::execute($method, $path, $params);
    }

    public function item($id = null)
    {
        // Load the menu.
        try {
            $resource = $this->loadResource($id, $this->build($this->request->post));
        } catch (\Exception $e) {
            return $this->render('@gantry-admin/pages/menu/menu.html.twig', $this->params);
        }

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
        $this->params['default_menu'] = $resource->hasDefaultMenu() ? $resource->getDefaultMenuName() : false;
        $this->params['menu'] = $resource;
        $this->params['path'] = implode('/', $path);

        // Detect special case to fetch only single column group.
        $group = $this->request->get['group'];

        if (empty($this->params['ajax']) || empty($this->request->get['inline'])) {
            // Handle special case to fetch only one column group.
            if (count($path) > 0) {
                $this->params['columns'] = $resource[$path[0]];
            }
            if (count($path) > 1) {
                $this->params['column'] = isset($group) ? (int) $group : $resource[implode('/', array_slice($path, 0, 2))]->group;
                $this->params['override'] = $item;
            }

            return $this->render('@gantry-admin//pages/menu/menu.html.twig', $this->params);

        } else {
            // Get layout name.
            $layout = $this->layoutName(count($path) + (int) isset($group));

            $this->params['item'] = $item;
            $this->params['group'] = isset($group) ? (int) $group : $resource[implode('/', array_slice($path, 0, 2))]->group;

            return $this->render('@gantry-admin/menu/' . $layout . '.html.twig', $this->params) ?: '&nbsp;';
        }
    }

    public function edit($id)
    {
        $resource = $this->loadResource($id);
        $input = $this->build($this->request->post);
        if ($input) {
            $resource->config()->merge(['settings' => $input['settings']]);
        }

        // Fill parameters to be passed to the template file.
        $this->params['id'] = $resource->name();
        $this->params['blueprints'] = $this->loadBlueprints();
        $this->params['data'] = ['settings' => $resource->settings()];

        return $this->render('@gantry-admin/pages/menu/edit.html.twig', $this->params);
    }

    public function save($id = null)
    {
        $resource = $this->loadResource($id);

        $data = $this->build($this->request->post);

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $filename = $locator->findResource("gantry-config://menu/{$resource->name()}.yaml", true, true);

        // Fire save event.
        $event = new Event;
        $event->gantry = $this->container;
        $event->theme = $this->container['theme'];
        $event->controller = $this;
        $event->resource = $id;
        $event->menu = $data;
        $this->container->fireEvent('admin.menus.save', $event);

        $file = YamlFile::instance($filename);
        $file->settings(['inline' => 99]);
        $file->save($data->toArray());
        $file->free();
    }

    public function editItem($id)
    {
        // All extra arguments become the path.
        $path = array_slice(func_get_args(), 1);
        $keyword = end($path);

        // Special case: validate instead of fetching menu item.
        if ($this->method == 'POST' && $keyword == 'validate') {
            $params = array_slice(func_get_args(), 0, -1);
            return call_user_func_array([$this, 'validateitem'], $params);
        }

        $path = html_entity_decode(implode('/', $path), ENT_COMPAT | ENT_HTML5, 'UTF-8');

        // Load the menu.
        $resource = $this->loadResource($id);

        // Get menu item and make sure it exists.
        /** @var Item $item */
        $item = $resource[$path];
        if (!$item) {
            throw new \RuntimeException('Menu item not found', 404);
        }
        $data = $this->request->post->getJsonArray('item');
        if ($data) {
            $item->update($data);
        }

        // Load blueprints for the menu item.
        $blueprints = $this->loadBlueprints('menuitem');

        $this->params = [
                'id'         => $resource->name(),
                'path'       => $path,
                'blueprints' => ['fields' => $blueprints['form/fields/items/fields']],
                'data'       => $item->toArray() + ['path' => $path],
            ] + $this->params;

        return $this->render('@gantry-admin/pages/menu/menuitem.html.twig', $this->params);
    }

    public function particle()
    {
        $data = $this->request->post['item'];
        if ($data) {
            $data = json_decode($data, true);
        } else {
            $data = $this->request->post->getArray();
        }

        $name = isset($data['particle']) ? $data['particle'] : null;

        $block = BlueprintForm::instance('menu/block.yaml', 'gantry-admin://blueprints');
        $blueprints = $this->container['particles']->getBlueprintForm($name);

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

        return $this->render('@gantry-admin/pages/menu/particle.html.twig', $this->params);
    }


    public function validateParticle($name)
    {
        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        // Load particle blueprints and default settings.
        $validator = new BlueprintSchema;
        $validator->embed('options', $this->container['particles']->get($name));

        $blueprints = $this->container['particles']->getBlueprintForm($name);

        // Create configuration from the defaults.
        $data = new Config([],
            function () use ($validator) {
                return $validator;
            }
        );

        $data->set('type', 'particle');
        $data->set('particle', $name);
        $data->set('title', $this->request->post['title'] ?: $blueprints->post['name']);
        $data->set('options.particle', $this->request->post->getArray("particles.{$name}"));
        $data->def('options.particle.enabled', 1);
        $data->set('enabled', $data->get('options.particle.enabled'));

        $block = $this->request->post->getArray('block');
        foreach ($block as $key => $param) {
            if ($param === '') {
                unset($block[$key]);
            }
        }

        $data->join('options.block', $block);

        // TODO: validate

        // Fill parameters to be passed to the template file.
        $this->params['item'] = (object) $data->toArray();

        $html = $this->render('@gantry-admin/menu/item.html.twig', $this->params);

        return new JsonResponse(['item' => $data->toArray(), 'html' => $html]);
    }

    public function selectModule()
    {
        return $this->render('@gantry-admin/modals/module-picker.html.twig', $this->params);
    }

    public function selectWidget()
    {
        $this->params['next'] = 'menu/widget';

        return $this->render('@gantry-admin/modals/widget-picker.html.twig', $this->params);
    }

    public function widget()
    {
        $data = $this->request->post->getJson('item');
        $path = [$data->widget];
        $this->params['scope'] = 'menu';

        return $this->executeForward('widget', 'POST', $path, $this->params);
    }

    public function selectParticle()
    {
        $groups = [
            'Particles' => ['particle' => []],
        ];

        $particles = [
            'position'    => [],
            'spacer'      => [],
            'system'      => [],
            'particle'    => [],
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

        $this->params += [
            'particles' => $groups,
            'route' => 'menu/particle',
        ];

        return $this->render('@gantry-admin/modals/particle-picker.html.twig', $this->params);
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
        $data = new Config($this->request->post->getArray(), $callable);

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

        // Create configuration from the defaults.
        $data = new Config($this->request->post->getArray(), $callable);

        // TODO: validate

        $item = $resource[implode('/', $path)];
        $item->update($data->toArray());

        // Fill parameters to be passed to the template file.
        $this->params['id'] = $resource->name();
        $this->params['item'] = $item;
        $this->params['group'] = isset($group) ? $group : $resource[implode('/', array_slice($path, 0, 2))]->group;

        if (!$item->title) {
            throw new \RuntimeException('Title from the Menu Item should not be empty', 400);
        }

        $html = $this->render('@gantry-admin/menu/item.html.twig', $this->params);

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
     * @return \Gantry\Component\Menu\AbstractMenu
     * @throws \RuntimeException
     */
    protected function loadResource($id, Config $config = null)
    {
        /** @var MenuObject $menus */
        $menus = $this->container['menu'];

        return $menus->instance(['menu' => $id, 'admin' => true], $config);
    }

    /**
     * Load blueprints.
     *
     * @param string $name
     *
     * @return BlueprintForm
     */
    protected function loadBlueprints($name = 'menu')
    {
        return BlueprintForm::instance("menu/{$name}.yaml", 'gantry-admin://blueprints');
    }


    public function build(Input $input)
    {
        try {
            $items = $input->get('items');
            if ($items && $items[0] !== '{' && $items[0] !== '[') {
                $items = urldecode((string)base64_decode($items));
            }
            $items = json_decode($items, true);

            $settings = $input->getJsonArray('settings');
            $order = $input->getJsonArray('ordering');
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid menu structure', 400);
        }

        if (!$items && !$settings && !$order) {
            return null;
        }


        krsort($order);
        $ordering = ['' => []];
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
            $particleIcon = isset($particle['icon']) ? $particle['icon'] : null;
            $list[$type][$name] = ['name' => $particleName, 'icon' => $particleIcon];
        }

        return $list;
    }

    protected function executeForward($resource, $method = 'GET', $path, $params = [])
    {
        $class = '\\Gantry\\Admin\\Controller\\Json\\' . strtr(ucwords(strtr($resource, '/', ' ')), ' ', '\\');
        if (!class_exists($class)) {
            throw new \RuntimeException('Page not found', 404);
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
