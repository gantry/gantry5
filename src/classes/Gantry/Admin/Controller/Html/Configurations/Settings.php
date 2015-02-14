<?php
namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\Config;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Gantry;
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
        $this->params['particles'] = $this->container['particles']->group();
        $this->params['route']  = "configurations.{$this->params['configuration']}.settings";

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/settings/settings.html.twig', $this->params);
    }

    public function display($id)
    {
        $particle = $this->container['particles']->get($id);
        $blueprints = new BlueprintsForm($particle);
        $prefix = 'particles.' . $id;

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

        $particle = $this->container['particles']->get($id);

        // Load blueprints.
        $blueprints = new BlueprintsForm($particle);

        list($fields, $path, $value) = $blueprints->resolve(array_slice($path, 1), '/');

        if (!$fields) {
            throw new \RuntimeException('Page Not Found', 404);
        }

        $fields['is_current'] = true;

        // Get the prefix.
        $prefix = "particles.{$id}." . implode('.', $path);
        if ($value !== null) {
            $parent = $fields;
            $fields = ['fields' => $fields['fields']];
            $prefix .= '.' . $value;
        }
        array_pop($path);

        $this->params = [
                'blueprints' => $fields,
                'data' =>  $this->container['config']->get($prefix),
                'parent' => $path
                    ? "configurations/{$this->params['configuration']}/settings/particles/{$id}/" . implode('/', $path)
                    : "configurations/{$this->params['configuration']}/settings/particles/{$id}",
                'route' => 'settings.' . $prefix
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/settings/field.html.twig', $this->params);
    }

    public function save($id = null)
    {
        $data = $id ? [$id => $_POST] : $_POST['particles'];

        foreach ($data as $name => $values) {
            $this->saveItem($name, $values);
        }

        return $id ? $this->display($id) : $this->index();
    }

    protected function saveItem($id, $data)
    {
        $blueprints = new BlueprintsForm($this->container['particles']->get($id));
        $config = new Config($data, function() use ($blueprints) { return $blueprints; });

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $configuration = $this->params['configuration'];
        $save_dir = $locator->findResource("gantry-config://{$configuration}/particles", true, true);
        $filename = "{$save_dir}/{$id}.yaml";

        $file = YamlFile::instance($filename);
        $file->save($config->toArray());
    }

    public function reset($id)
    {
        $this->params += [
            'data' => [],
        ];

        return $this->display($id);
    }
}
