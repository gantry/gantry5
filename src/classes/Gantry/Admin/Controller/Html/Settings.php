<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Settings extends HtmlController
{
    public function index(array $params)
    {
        $files = $this->locateParticles();

        $particles = [];
        foreach ($files as $key => $file) {
            $filename = key($file);
            $particles[$key] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
        }

        $params['particles'] = $particles;
        return $this->container['admin.theme']->render('@gantry-admin/settings.html.twig', $params);
    }

    public function display(array $params)
    {
        $files = $this->locateParticles();

        $key = !empty($params['id']) ? $params['id'] : null;

        $particles = [];
        if (!empty($files[$key])) {
            $filename = $files[$key];
            $particles[$key] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
        }

        $params['particles'] = $particles;
        return $this->container['admin.theme']->render('@gantry-admin/settings.html.twig', $params);
    }

    protected function locateParticles() {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];
        $paths = $locator->findResources('gantry-particles://');

        return (new ConfigFileFinder)->listFiles($paths);
    }
}
