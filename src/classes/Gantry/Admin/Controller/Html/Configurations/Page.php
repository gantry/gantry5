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

namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\Blueprints\Blueprints;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Page extends HtmlController
{
    protected $httpVerbs = [
        'GET'    => [
            '/' => 'index'
        ],
        'POST'   => [
            '/'     => 'save',
            '/*'    => 'save',
            '/*/**' => 'formfield'
        ],
        'PUT'    => [
            '/' => 'save'
        ],
        'PATCH'  => [
            '/' => 'save'
        ],
        'DELETE' => [
            '/' => 'forbidden'
        ]
    ];

    public function index()
    {
        $configuration = $this->params['configuration'];

        if ($configuration == 'default') {
            $this->params['overrideable'] = false;
        } else {
            $this->params['defaults']     = $this->container['defaults'];
            $this->params['overrideable'] = true;
        }

        $deprecated = $this->getDeprecatedAtoms();
        if ($deprecated) {
            $this->container['config']->set('page.head.atoms', $deprecated);
        }

        $this->params['page']             = $this->container['page']->group();
        $this->params['atoms']            = $this->getAtoms();
        $this->params['atoms_deprecated'] = $deprecated;
        $this->params['route']            = "configurations.{$this->params['configuration']}";
        $this->params['page_id']          = $configuration;

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/page/page.html.twig', $this->params);
    }

    public function save($id = null)
    {
        $data = $id ? [$id => $this->request->post->getArray()] : $this->request->post->getArray('page');

        foreach ($data as $name => $values) {
            $this->saveItem($name, $values);
        }

        // Fire save event.
        $event             = new Event;
        $event->gantry     = $this->container;
        $event->theme      = $this->container['theme'];
        $event->controller = $this;
        $event->data       = $data;
        $this->container->fireEvent('admin.page.save', $event);

        return $id ? $this->display($id) : $this->index();
    }

    public function formfield($id)
    {
        $path = func_get_args();

        if (end($path) == 'validate') {
            return call_user_func_array([$this, 'validate'], $path);
        }

        $setting = $this->container['page']->get($id);

        // Load blueprints.
        $blueprints = new BlueprintsForm($setting);

        list($fields, $path, $value) = $blueprints->resolve(array_slice($path, 1), '/');

        if (!$fields) {
            throw new \RuntimeException('Page Not Found', 404);
        }

        $data = $this->request->post->getJsonArray('data');

        $offset = "page.{$id}." . implode('.', $path);
        if ($value !== null) {
            $parent = $fields;
            $fields = ['fields' => $fields['fields']];
            $offset .= '.' . $value;
            $data   = $data ?: $this->container['config']->get($offset);
            $data   = ['data' => $data];
            $prefix = 'data.';
        } else {
            $data   = $data ?: $this->container['config']->get($offset);
            $prefix = 'data';
        }

        $fields['is_current'] = true;

        array_pop($path);

        $configuration = "configurations/{$this->params['configuration']}";
        $this->params  = [
                'configuration' => $configuration,
                'blueprints'    => $fields,
                'data'          => $data,
                'prefix'        => $prefix,
                'parent'        => $path
                    ? "$configuration/page/{$id}/" . implode('/', $path)
                    : "$configuration/page/{$id}",
                'route'         => $offset
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }
        if (isset($parent['value'])) {
            $this->params['title'] = $parent['value'];
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/settings/field.html.twig', $this->params);
    }

    protected function validate($setting)
    {
        $path = implode('.', array_slice(func_get_args(), 1, -1));

        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        // Load particle blueprints.
        $validator = $this->container['particles']->get($setting);

        // Create configuration from the defaults.
        $data = new Config(
            [],
            function () use ($validator) {
                return $validator;
            }
        );

        $data->join($path, $this->request->post->getArray('data'));

        // TODO: validate

        return new JsonResponse(['data' => $data->get($path)]);
    }

    protected function saveItem($id, $data)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $configuration = $this->params['configuration'];

        // Move atoms out of layout.
        if ($id === 'head') {
            $layout = Layout::instance($configuration);
            if (is_array($layout->atoms())) {
                $layout->save(false);
            }
        }

        $save_dir      = $locator->findResource("gantry-config://{$configuration}/page", true, true);
        $filename      = "{$save_dir}/{$id}.yaml";

        $file = YamlFile::instance($filename);
        if (!is_array($data)) {
            if ($file->exists()) {
                $file->delete();
            }
        } else {
            $blueprints = new BlueprintsForm($this->container['page']->get($id));
            $config     = new Config($data, function () use ($blueprints) { return $blueprints; });

            $file->save($config->toArray());
        }
        $file->free();
    }

    protected function getDeprecatedAtoms()
    {
        $id     = $this->params['configuration'];
        $layout = Layout::instance($id);

        return $layout->atoms();
    }

    protected function getAtoms($onlyEnabled = false)
    {
        $config = $this->container['config'];

        $atoms = $this->container['particles']->all();

        $list = [];
        foreach ($atoms as $name => $atom) {
            $type     = isset($atom['type']) ? $atom['type'] : 'atom';
            $atomName = isset($atom['name']) ? $atom['name'] : $name;

            if (!$onlyEnabled || $config->get("particles.{$name}.enabled", true)) {
                $list[$type][$name] = $atomName;
            }
        }

        return $list['atom'];
    }
}
