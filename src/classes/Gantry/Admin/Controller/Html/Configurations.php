<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\Blueprints;
use Gantry\Component\Config\Config;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Layout\LayoutReader;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Gantry;
use \RocketTheme\Toolbox\Blueprints\Blueprints as Validator;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Configurations extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'             => 'index',
            '/create'       => 'create',
            '/create/*'     => 'create',
            '/*'            => 'edit',
            '/*/*'          => 'undefined',
            '/*/*/*'        => 'particle'
        ],
        'POST' => [
            '/'             => 'undefined',
            '/*'            => 'save',
            '/*/*'          => 'undefined',
            '/*/*/*'        => 'particle',
            '/particles'    => 'undefined',
            '/particles/*'  => 'undefined',
            '/particles/*/validate' => 'validate'
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

        $layouts_user = array_filter($layouts, function($val) { return strpos($val, 'presets/') !== 0 && substr($val, 0, 1) !== '_'; });
        $layouts_core = array_filter($layouts, function($val) { return strpos($val, 'presets/') !== 0 && substr($val, 0, 1) === '_'; });
        $this->params['layouts'] = ['user' => $layouts_user, 'core' => $layouts_core];

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/configurations.html.twig', $this->params);
    }

    public function create($id = null)
    {
        if (!$id) {
            // TODO: we might want to display list of options here
            throw new \RuntimeException('Not Implemented', 404);
        }

        $layout = $this->getLayout("presets/{$id}");
        if (!$layout) {
            throw new \RuntimeException('Preset not found', 404);
        }
        $this->params['page_id'] = $id;
        $this->params['layout'] = $layout;

        return $this->container['admin.theme']->render('@gantry-admin/pages/layouts/create.html.twig', $this->params);
    }

    public function edit($id)
    {
        $layout = $this->getLayout($id);
        if (!$layout) {
            throw new \RuntimeException('Layout not found', 404);
        }

        $this->params['page_id'] = $id;
        $this->params['layout'] = $layout;
        $this->params['id'] = ucwords(str_replace('_', ' ', ltrim($id, '_')));

        return $this->container['admin.theme']->render('@gantry-admin/pages/layouts/edit.html.twig', $this->params);
    }

    public function save($page)
    {
        $title = isset($_POST['title']) ? $_POST['title'] : ucfirst($page);
        $layout = isset($_POST['layout']) ? json_decode($_POST['layout']) : null;

        if (!$layout) {
            throw new \RuntimeException('Error while saving layout: Structure missing', 400);
        }

        $new_page = preg_replace('|[^a-z0-9_-]|', '', strtolower($title));

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $save_dir = $locator->findResource('gantry-layouts://', true, true);
        $filename = $save_dir . "{$new_page}.json";

        if ($page != $new_page && is_file($filename)) {
            throw new \RuntimeException("Error while saving layout: Layout '{$new_page}' already exists", 403);
        }

        $file = JsonFile::instance($filename);
        $file->save($layout);
    }

    public function particle($page, $type, $id)
    {
        $layout = $this->getLayout($page);
        if (!$layout) {
            throw new \RuntimeException('Layout not found', 404);
        }

        if (isset($_POST)) {
            $item = (object) [
                'id' => $id,
                'type' => isset($_POST['type']) ? $_POST['type'] : $type,
                'subtype' => isset($_POST['subtype']) ? $_POST['subtype'] : null,
                'attributes' => (object) (isset($_POST['options']) ? $_POST['options'] : []),
                'block' => new \stdClass
            ];
            if (isset($_POST['block'])) {
                $item->block = (object) $_POST['block'];
            }
        } else {
            $item = $this->find($layout, $id);
        }

        $name = isset($item->subtype) ? $item->subtype : $type;

        if (is_object($item) && $name) {
            $prefix = 'particles.' . $name;
            $defaults = (array) $this->container['config']->get($prefix);
            // TODO: Use blueprints to merge configuration.
            $data = (array) $item->attributes + $defaults;
            if ($type == 'section' || $type == 'grid') {
                $extra = null;
                $blueprints = new Blueprints(CompiledYamlFile::instance("gantry-admin://blueprints/layout/{$name}.yaml")->content());
            } else {
                $extra = new Blueprints(CompiledYamlFile::instance("gantry-admin://blueprints/layout/block.yaml")->content());
                $blueprints = new Blueprints($this->container['particles']->get($name));
            }

            $this->params += [
                'extra' => $extra,
                'block' => $item->block,
                'particle' => $blueprints,
                'data' =>  $data,
                'id' => $name,
                'parent' => 'settings',
                'route' => 'settings.' . $prefix,
                'action' => str_replace('.', '/', 'pages.' . $prefix . '.validate'),
                'skip' => ['enabled']
            ];

            if ($extra) {
                $result = $this->container['admin.theme']->render('@gantry-admin/pages/layouts/particle.html.twig', $this->params);
            } else {
                $result = $this->container['admin.theme']->render('@gantry-admin/pages/layouts/section.html.twig', $this->params);
            }

            if (!empty($this->params['ajax'])) {
                return new JsonResponse(['html' => $result, 'defaults' => ['particle' => $defaults]]);
            }
            return $result;
        }
        throw new \RuntimeException('No configuration exists yet', 404);
    }

    public function validate($particle)
    {
        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        // Load particle blueprints and default settings.
        $validator = new Validator();
        $validator->embed('options', $this->container['particles']->get($particle));
        $callable = function () use ($validator) { return $validator; };
        $defaults = (array) $this->container['config']->get("particles.{$particle}");

        // Create configuration from the defaults.
        $data = new Config(
            [
                'type' => 'particle',
                'subtype' => $particle,
                'options' => $defaults,
                'block' => [],
            ],
            $callable);

        // Join POST data.
        $data->join('options', $_POST['particle']);
        $data->join('block', $_POST['block']);

        // TODO: validate

        return new JsonResponse(['data' => $data->toArray()]);
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
