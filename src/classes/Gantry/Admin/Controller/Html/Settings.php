<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\Blueprints;
use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Gantry;
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
            '/particles'  => 'store'
        ],
        'PUT' => [
            '/particles/*' => 'replace'
        ],
        'PATCH' => [
            '/particles/*' => 'update'
        ],
        'DELETE' => [
            '/particles/*' => 'destroy'
        ]
    ];

    public function index()
    {
        $this->params['particles'] = $this->container['particles']->all();

        return $this->container['admin.theme']->render('@gantry-admin/settings.html.twig', $this->params);
    }

    public function display($id)
    {
        $particle = $this->container['particles']->get($id);
        $blueprints = new Blueprints($particle);
        $prefix = 'particles.' . $id;

        $this->params += [
            'particle' => $particle,
            'blueprints' => $blueprints['form'],
            'data' =>  Gantry::instance()['config']->get($prefix),
            'id' => $id,
            'parent' => 'settings',
            'route' => 'settings.' . $prefix,
            'skip' => ['enabled']
            ];

        return $this->container['admin.theme']->render('@gantry-admin/settings_item.html.twig', $this->params);
    }

    public function formfield($id)
    {
        $path = func_get_args();

        $particle = $this->container['particles']->get($id);

        // Load blueprints.
        $blueprints = new Blueprints($particle);

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
        array_pop($path);

        $this->params = [
                'blueprints' => $fields,
                'data' =>  $this->container['config']->get($prefix),
                'parent' => $path ? "settings/particles/{$id}/" . implode('/', $path) : "settings/particles/{$id}",
                'route' => 'settings.' . $prefix
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }

        return $this->container['admin.theme']->render('@gantry-admin/settings_field.html.twig', $this->params);
    }
}
