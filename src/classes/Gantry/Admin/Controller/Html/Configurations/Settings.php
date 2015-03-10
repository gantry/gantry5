<?php
namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\Config;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout as LayoutObject;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\Blueprints\Blueprints;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Settings extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'                 => 'index',
            '/particles'        => 'undefined',
            '/particles/*'      => 'display',
            '/particles/*/**'   => 'formfield',
        ],
        'POST' => [
            '/'                 => 'save',
            '/particles'        => 'forbidden',
            '/particles/*'      => 'save',
            '/particles/*/**'   => 'formfield'
        ],
        'PUT' => [
            '/'            => 'save',
            '/particles'   => 'forbidden',
            '/particles/*' => 'save'
        ],
        'PATCH' => [
            '/'            => 'save',
            '/particles'   => 'forbidden',
            '/particles/*' => 'save'
        ],
        'DELETE' => [
            '/'            => 'forbidden',
            '/particles'   => 'forbidden',
            '/particles/*' => 'reset'
        ]
    ];

    public function index()
    {
        $configuration = $this->params['configuration'];

        if($configuration == 'default') {
            $this->params['overrideable'] = false;
        } else {
            $this->params['defaults'] = $this->container['defaults'];
            $this->params['overrideable'] = true;
        }

        $this->params['particles'] = $this->container['particles']->group();
        $this->params['route']  = "configurations.{$this->params['configuration']}.settings";
        $this->params['page_id'] = $configuration;

        //$this->params['layout'] = LayoutObject::instance($configuration);

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/settings/settings.html.twig', $this->params);
    }

    public function display($id)
    {
        $configuration = $this->params['configuration'];
        $particle = $this->container['particles']->get($id);
        $blueprints = new BlueprintsForm($particle);
        $prefix = 'particles.' . $id;

        if($configuration == 'default') {
            $this->params['overrideable'] = false;
        } else {
            $this->params['defaults'] = $this->container['defaults']->get($prefix);
            $this->params['overrideable'] = true;
        }

        $this->params += [
            'particle' => $blueprints,
            'data' =>  Gantry::instance()['config']->get($prefix),
            'id' => $id,
            'parent' => "configurations/{$this->params['configuration']}/settings",
            'route'  => "configurations.{$this->params['configuration']}.settings.{$prefix}",
            'skip' => ['enabled']
            ];

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/settings/item.html.twig', $this->params);
    }

    public function formfield($id)
    {
        $path = func_get_args();

        if (end($path) == 'validate') {
            return call_user_func_array([$this, 'validate'], $path);
        }

        $particle = $this->container['particles']->get($id);

        // Load blueprints.
        $blueprints = new BlueprintsForm($particle);

        list($fields, $path, $value) = $blueprints->resolve(array_slice($path, 1), '/');

        if (!$fields) {
            throw new \RuntimeException('Page Not Found', 404);
        }

        // Get the prefix.
        $prefix = "particles.{$id}." . implode('.', $path);
        if ($value !== null) {
            $parent = $fields;
            $fields = ['fields' => $fields['fields']];
            $prefix .= '.' . $value;
        }
        $fields['is_current'] = true;

        array_pop($path);

        $configuration = "configurations/{$this->params['configuration']}";
        $this->params = [
                'configuration' => $configuration,
                'blueprints' => $fields,
                'data' =>  $this->container['config']->get($prefix),
                'parent' => $path
                    ? "$configuration/settings/particles/{$id}/" . implode('/', $path)
                    : "$configuration/settings/particles/{$id}",
                'route' => 'settings.' . $prefix
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }
        if (isset($parent['value'])) {
            $this->params['title'] = $parent['value'];
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/settings/field.html.twig', $this->params);
    }

    public function validate($particle)
    {
        $path = implode('.', array_slice(func_get_args(), 1, -1));

        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        // Load particle blueprints.
        $validator = $this->container['particles']->get($particle);

        // Create configuration from the defaults.
        $data = new Config(
            [],
            function () use ($validator) {
                return $validator;
            }
        );

        /** @var Request $request */
        $request = $this->container['request'];
        $data->join($path, $request->getArray());

        // TODO: validate

        return new JsonResponse(['data' => $data->get($path)]);
    }

    public function save($id = null)
    {
        /** @var Request $request */
        $request = $this->container['request'];

        $data = $id ? [$id => $request->getArray()] : $request->getArray('particles');

        foreach ($data as $name => $values) {
            $this->saveItem($name, $values);
        }

        return $id ? $this->display($id) : $this->index();
    }

    protected function saveItem($id, $data)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $configuration = $this->params['configuration'];
        $save_dir = $locator->findResource("gantry-config://{$configuration}/particles", true, true);
        $filename = "{$save_dir}/{$id}.yaml";

        $file = YamlFile::instance($filename);
        if (!is_array($data)) {
            if ($file->exists()) {
                $file->delete();
            }
        } else {
            $blueprints = new BlueprintsForm($this->container['particles']->get($id));
            $config = new Config($data, function() use ($blueprints) { return $blueprints; });

            $file->save($config->toArray());
        }
    }

    public function reset($id)
    {
        $this->params += [
            'data' => [],
        ];

        return $this->display($id);
    }
}
