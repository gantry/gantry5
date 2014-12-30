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
        $files = $this->locateParticles();

        $particles = [];
        foreach ($files as $key => $file) {
            $filename = key($file);
            $particles[$key] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
        }

        $this->params['particles'] = $particles;
        return $this->container['admin.theme']->render('@gantry-admin/settings.html.twig', $this->params);
    }

    public function display($id)
    {
        $files = $this->locateParticles();

        if (empty($files[$id])) {
            throw new \RuntimeException("Settings for '{$id}' not found.", 404);
        }

        $filename = key($files[$id]);
        $prefix = 'particles.' . $id;
        $particle = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
        $blueprints = new Blueprints($particle);

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

    public function formfield($particle)
    {
        $path = func_get_args();

        $files = $this->locateParticles();

        if (empty($files[$particle])) {
            throw new \RuntimeException("Settings for '$particle' not found", 404);
        }

        // Load blueprints.
        $filename = key($files[$particle]);
        $blueprints = new Blueprints(CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content());

        // Get the form field.
        $i = 1;
        $id = null;
        $fields = $blueprints['form.fields.' . implode('.', array_slice($path, 1))];
        if ($fields) {
            $key = array_pop($path);
            $fields = [$key => $fields];
        } else {
            $parent = $blueprints['form.fields.' . implode('.', array_slice($path, 1, -1))];
            if ($parent['fields']) {
                $fields = $parent['fields'];
            }
            $i++;
        }
        if (!$fields) {
            throw new \RuntimeException("Page Not Found", 404);
        }

        // Get the prefix.
        $prefix = 'particles.' . implode('.', $path);

        $this->params = [
                'blueprints' => ['fields' => $fields],
                'data' =>  Gantry::instance()['config']->get($prefix),
                'parent' => 'settings/particles/' . (count($path) > $i ? implode('/', array_slice($path, 0, -$i)) : $particle),
                'route' => 'settings.' . $prefix
            ] + $this->params;

        if (!empty($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }

        return $this->container['admin.theme']->render('@gantry-admin/settings_field.html.twig', $this->params);
    }

    protected function locateParticles() {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];
        $paths = $locator->findResources('gantry-particles://');

        return (new ConfigFileFinder)->listFiles($paths);
    }
}
