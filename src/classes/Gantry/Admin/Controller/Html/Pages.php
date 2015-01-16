<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\Blueprints;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Layout\LayoutReader;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Pages extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'             => 'index',
            '/create'       => 'create',
            '/create/*'     => 'create',
            '/*'            => 'display',
            '/*/edit'       => 'edit',
            '/*/*'          => 'undefined',
            '/*/*/*'        => 'particle'
        ],
        'POST' => [
            '/'             => 'store',
            '/*'            => 'undefined',
            '/*/*'          => 'undefined',
            '/*/*/*'        => 'particle'
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
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $finder = new \Gantry\Component\Config\ConfigFileFinder();
        $files = $finder->getFiles($locator->findResources('gantry-layouts://', false), '|\.json$|');
        $files += $finder->getFiles($locator->findResources('gantry-layouts://', false));
        $layouts = array_keys($files);
        sort($layouts);

        $layouts = array_filter($layouts, function($val) { return strpos($val, 'presets/') !== 0; });
        $this->params['layouts'] = $layouts;

        return $this->container['admin.theme']->render('@gantry-admin/pages_index.html.twig', $this->params);
    }

    public function create($id = null)
    {
        if (!$id) {
            // TODO:
            throw new \RuntimeException('Not Implemented', 404);
        }

        $layout = $this->getLayout("presets/{$id}");
        if (!$layout) {
            throw new \RuntimeException('Preset not found', 404);
        }
        $this->params['page_id'] = $id;
        $this->params['layout'] = $layout;

        return $this->container['admin.theme']->render('@gantry-admin/pages_create.html.twig', $this->params);
    }

    public function edit($id)
    {
        $layout = $this->getLayout($id);
        if (!$layout) {
            throw new \RuntimeException('Layout not found', 404);
        }

        $this->params['page_id'] = $id;
        $this->params['layout'] = $layout;
        $this->params['id'] = ucwords($id);

        return $this->container['admin.theme']->render('@gantry-admin/pages_edit.html.twig', $this->params);
    }

    public function particle($page, $type, $id)
    {
        $layout = $this->getLayout($page);
        if (!$layout) {
            throw new \RuntimeException('Layout not found', 404);
        }

        if (!empty($_POST)) {
            $item = (object) [
                'id' => $id,
                'type' => isset($_POST['type']) ? $_POST['type'] : $type,
                'attributes' => (object) isset($_POST['options']) ? $_POST['options'] : [],
                'block' => (object) isset($_POST['block']) ? $_POST['block'] : []
            ];
        } else {
            $item = $this->find($layout, $id);
        }

        if ($type == 'particle') {
            $name = isset($item->type) ? $item->type : null;
        } else {
            $name = $type;
        }

        if (is_object($item) && $name) {
            $prefix = 'particles.' . $name;
            // TODO: Use blueprints to merge configuration.
            $data = (array) $item->attributes + (array) $this->container['config']->get($prefix);
            if ($type == 'section') {
                $blueprints = new Blueprints(CompiledYamlFile::instance("gantry-admin://blueprints/layout/{$name}.yaml")->content());
            } else {
                $blueprints = new Blueprints($this->container['particles']->get($name));
            }

            $this->params += [
                'particle' => $blueprints,
                'data' =>  $data,
                'id' => $name,
                'parent' => 'settings',
                'route' => 'settings.' . $prefix,
                'action' => str_replace('.', '/', 'pages.' . $prefix . '.validate'),
                'skip' => ['enabled']
            ];

            return $this->container['admin.theme']->render('@gantry-admin/pages_particle.html.twig', $this->params);
        }
        throw new \RuntimeException('No configuration exists yet', 404);
    }

    protected function getLayout($name)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $layout = null;
        $filename = $locator('gantry-layouts://' . $name . '.json');
        if ($filename) {
            $layout = JsonFile::instance($filename)->content();
        } else {
            $filename = $locator('gantry-layouts://' . $name . '.yaml');
            if ($filename) {
                $layout = LayoutReader::read($filename);
            }
        }

        return $layout;
    }

    protected function find($layout, $id)
    {
        if (!is_array($layout)) {
            return null;
        }
        foreach ($layout as $item) {
            if (is_object($item)) {
                if ($item->id == $id) {
                    return $item;
                }
                $result = $this->find($item->children, $id);
                if ($result) {
                    return $result;
                }
            }
        }
        return null;
    }
}
