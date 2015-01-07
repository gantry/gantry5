<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\Blueprints;
use Gantry\Component\Controller\HtmlController;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\File\JsonFile;

class Pages extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'             => 'index',
            '/create'       => 'create',
            '/create/*'     => 'create',
            '/*'            => 'display',
            '/*/edit'       => 'edit',
            '/*/particle'   => 'undefined',
            '/*/particle/*' => 'particle'
        ],
        'POST' => [
            '/'             => 'store',
            '/*'            => 'undefined',
            '/*/particle'   => 'undefined',
            '/*/particle/*' => 'particle'
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
        return $this->container['admin.theme']->render('@gantry-admin/pages_index.html.twig', $this->params);
    }

    public function create($id = null)
    {
        $locator = $this->container['locator'];

        if (!$id) {
            // TODO:
            throw new \RuntimeException('Not Implemented', 404);
        } else {
            $layout = JsonFile::instance($locator('gantry-theme://layouts/presets/'.$id.'.json'))->content();
            if (!$layout) {
                throw new \RuntimeException('Preset not found', 404);
            }
            $this->params['page_id'] = $id;
            $this->params['layout'] = $layout;
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages_create.html.twig', $this->params);
    }

    public function edit($id)
    {
        $locator = $this->container['locator'];

        // TODO: remove hardcoded layout.
        $layout = JsonFile::instance($locator('gantry-theme://layouts/test.json'))->content();
        if (!$layout) {
            throw new \RuntimeException('Layout not found', 404);
        }

        $this->params['page_id'] = $id;
        $this->params['layout'] = $layout;
        $this->params['id'] = ucwords($id);

        return $this->container['admin.theme']->render('@gantry-admin/pages_edit.html.twig', $this->params);
    }

    public function particle($page, $particle)
    {
        $locator = $this->container['locator'];

        // TODO: remove hardcoded layout.
        $layout = JsonFile::instance($locator('gantry-theme://layouts/test.json'))->content();
        if (!$layout) {
            throw new \RuntimeException('Layout not found', 404);
        }

        if (isset($_POST['options'])) {
            $item = (object) [
                'id' => $particle,
                'type' => 'particle',
                'attributes' => (object) $_POST['options']
            ];
            //var_dump($item);die();
        } else {
            $item = $this->find($layout, $particle);
        }

        if (is_object($item) && $item->type == 'particle' && isset($item->attributes->name)) {
            $id = $item->attributes->name;
            $prefix = 'particles.' . $id;
            // TODO: Use blueprints to merge configuration.
            $data = (array) $item->attributes + (array) $this->container['config']->get($prefix);
            $blueprints = new Blueprints($this->container['particles']->get($id));

            $this->params += [
                'particle' => $blueprints,
                'data' =>  $data,
                'id' => $id,
                'parent' => 'settings',
                'route' => 'settings.' . $prefix,
                'action' => str_replace('.', '/', 'pages.' . $prefix . '.validate'),
                'skip' => ['enabled']
            ];

            return $this->container['admin.theme']->render('@gantry-admin/pages_particle.html.twig', $this->params);
        }
        throw new \RuntimeException('No configuration exists yet', 404);
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
