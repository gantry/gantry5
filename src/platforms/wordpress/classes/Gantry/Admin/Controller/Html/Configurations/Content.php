<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Admin\HtmlController;
use Gantry\Component\Config\Config;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Gantry;
use Gantry\Framework\Services\ConfigServiceProvider;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Content extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'       => 'index',
            '/*'      => 'undefined',
            '/*/*'    => 'display',
            '/*/*/**' => 'formfield',
        ],
        'POST' => [
            '/'       => 'save',
            '/*'      => 'forbidden',
            '/*/*'    => 'save',
            '/*/*/**' => 'formfield'
        ],
        'PUT' => [
            '/'       => 'save',
            '/*'      => 'forbidden',
            '/*/*'    => 'save'
        ],
        'PATCH' => [
            '/'       => 'save',
            '/*'      => 'forbidden',
            '/*/*'    => 'save'
        ],
        'DELETE' => [
            '/'       => 'forbidden',
            '/*'      => 'forbidden',
            '/*/*'    => 'reset'
        ]
    ];

    public function index()
    {
        $outline = $this->params['outline'];

        if($outline == 'default') {
            $this->params['overrideable'] = false;
            $data = $this->container['config'];
        } else {
            $this->params['defaults'] = $this->container['defaults'];
            $this->params['overrideable'] = true;
            $data = ConfigServiceProvider::load($this->container, $outline, false, false);
        }

        $this->params['data'] = $data;
        $this->params['content'] = $this->container['content']->group();
        $this->params['route']  = "configurations.{$outline}.content";
        $this->params['page_id'] = $outline;

        return $this->render('@gantry-admin/pages/configurations/content/content.html.twig', $this->params);
    }

    public function display($group, $id = null)
    {
        $outline = $this->params['outline'];
        $blueprints = $this->container['content']->getBlueprintForm("{$group}/{$id}");
        $prefix = "content.{$group}.{$id}";

        if($outline == 'default') {
            $this->params['overrideable'] = false;
        } else {
            $this->params['defaults'] = $this->container['defaults']->get($prefix);
            $this->params['overrideable'] = true;
        }

        $this->params += [
            'particle' => $blueprints,
            'data' =>  Gantry::instance()['config']->get($prefix),
            'id' => "{$group}.{$id}", // FIXME?
            'parent' => "configurations/{$outline}/content",
            'route'  => "configurations.{$outline}.content.{$prefix}",
            'skip' => ['enabled']
            ];

        return $this->render('@gantry-admin/pages/configurations/content/item.html.twig', $this->params);
    }

    public function formfield($group, $id)
    {
        $path = func_get_args();

        if (end($path) == 'validate') {
            return call_user_func_array([$this, 'validate'], $path);
        }

        // Load blueprints.
        $blueprints = $this->container['content']->getBlueprintForm("{$group}/{$id}");

        list($fields, $path, $value) = $blueprints->resolve(array_slice($path, 1), '/');

        if (!$fields) {
            throw new \RuntimeException('Page Not Found', 404);
        }

        $data = $this->request->post->getJsonArray('data');

        $offset = "content.{$group}.{$id}." . implode('.', $path);
        if ($value !== null) {
            $parent = $fields;
            $fields = ['fields' => $fields['fields']];
            $offset .= '.' . $value;
            $data = $data ?: $this->container['config']->get($offset);
            $data = ['data' => $data];
            $prefix = 'data.';
        } else {
            $data = $data ?: $this->container['config']->get($offset);
            $prefix = 'data';
        }

        $fields['is_current'] = true;

        array_pop($path);

        $outline = $this->params['outline'];
        $configuration = "configurations/{$outline}";
        $this->params = [
                'configuration' => $configuration,
                'blueprints' => $fields,
                'data' => $data,
                'prefix' => $prefix,
                'parent' => $path
                    ? "$configuration/content/content/{$group}/{$id}/" . implode('/', $path)
                    : "$configuration/content/content/{$group}/{$id}",
                'route' => 'content.' . $offset
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }
        if (isset($parent['value'])) {
            $this->params['title'] = $parent['value'];
        }

        return $this->render('@gantry-admin/pages/configurations/content/field.html.twig', $this->params);
    }

    public function validate($group, $id)
    {
        $path = implode('.', array_slice(func_get_args(), 1, -2));

        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        // Load blueprints.
        $validator = $this->container['content']->get("{$group}/{$id}");

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

    public function save($group = null, $id = null)
    {
        $data = $id ? [$group => [$id => $this->request->post->getArray()]] : $this->request->post->getArray('content');

        foreach ($data as $group => $subgroups) {
            foreach ($subgroups as $name => $values) {
                $this->saveItem($group, $name, $values);
            }
        }

        // Fire save event.
        $event = new Event;
        $event->gantry = $this->container;
        $event->theme = $this->container['theme'];
        $event->controller = $this;
        $event->data = $data;
        $this->container->fireEvent('admin.content.save', $event);

        return $id ? $this->display($group, $id) : $this->index();
    }

    protected function saveItem($group, $id, $data)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $outline = $this->params['outline'];
        $save_dir = $locator->findResource("gantry-config://{$outline}/content", true, true);
        $filename = "{$save_dir}/{$group}/{$id}.yaml";

        $file = YamlFile::instance($filename);
        if (!is_array($data)) {
            if ($file->exists()) {
                $file->delete();
            }
        } else {
            $blueprints = $this->container['content']->getBlueprintForm("{$group}/{$id}");
            $config = new Config($data, function() use ($blueprints) { return $blueprints; });

            $file->save($config->toArray());
        }
        $file->free();
    }

    public function reset($group, $id)
    {
        $this->params += [
            'data' => [],
        ];

        return $this->display($group, $id);
    }
}
