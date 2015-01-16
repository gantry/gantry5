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

class Styles extends HtmlController
{

    protected $httpVerbs = [
        'GET' => [
            '/'              => 'index',
            '/styles'        => 'undefined',
            '/styles/*'      => 'display',
            '/styles/*/**'   => 'formfield',
        ],
        'POST' => [
            '/'         => 'forbidden',
            '/styles'   => 'forbidden',
            '/styles/*' => 'save'
        ],
        'PUT' => [
            '/'         => 'forbidden',
            '/styles'   => 'forbidden',
            '/styles/*' => 'save'
        ],
        'PATCH' => [
            '/'         => 'forbidden',
            '/styles'   => 'forbidden',
            '/styles/*' => 'save'
        ],
        'DELETE' => [
            '/'            => 'forbidden',
            '/styles'   => 'forbidden',
            '/styles/*' => 'reset'
        ]
    ];

    public function index()
    {
        $this->params['styles'] = $this->container['styles']->group();

        return $this->container['admin.theme']->render('@gantry-admin/styles.html.twig', $this->params);
    }

    public function display($id)
    {
        $style = $this->container['styles']->get($id);
        $blueprints = new Blueprints($style);
        $prefix = 'styles.' . $id;

        $this->params += [
            'style' => $blueprints,
            'data' =>  Gantry::instance()['config']->get($prefix),
            'id' => $id,
            'parent' => 'settings',
            'route' => 'settings.' . $prefix,
            'skip' => ['enabled']
        ];

        return $this->container['admin.theme']->render('@gantry-admin/styles/item.html.twig', $this->params);
    }

    public function formfield($id)
    {
        $path = func_get_args();

        $style = $this->container['styles']->get($id);

        // Load blueprints.
        $blueprints = new Blueprints($style);

        list($fields, $path, $value) = $blueprints->resolve(array_slice($path, 1), '/');

        if (!$fields) {
            throw new \RuntimeException('Page Not Found', 404);
        }

        // Get the prefix.
        $prefix = "styles.{$id}." . implode('.', $path);
        if ($value !== null) {
            $parent = $fields;
            $fields = ['fields' => $fields['fields']];
            $prefix .= '.' . $value;
        }
        array_pop($path);

        $this->params = [
                'blueprints' => $fields,
                'data' =>  $this->container['config']->get($prefix),
                'parent' => $path ? "settings/styles/{$id}/" . implode('/', $path) : "settings/styles/{$id}",
                'route' => 'settings.' . $prefix
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }

        return $this->container['admin.theme']->render('@gantry-admin/settings/field.html.twig', $this->params);
    }

    public function save($id)
    {
        $this->params += [
            'data' => $_POST,
        ];

        return $this->display($id);
    }

    public function reset($id)
    {
        $this->params += [
            'data' => [],
        ];

        return $this->display($id);
    }
}
