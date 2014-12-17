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
            throw new \RuntimeException("Settings for '$id' not found.", 404);
        }

        $filename = key($files[$id]);
        $this->params['particle'] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
        $this->params['id'] = $id;

        return $this->container['admin.theme']->render('@gantry-admin/settings_item.html.twig', $this->params);
    }

    public function form($id)
    {
        $files = $this->locateParticles();

        if (empty($files[$id])) {
            throw new \RuntimeException("Settings for '$id' not found.", 404);
        }

        $filename = key($files[$id]);
        $this->params['particle'] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
        $this->params['id'] = $id;

        return $this->container['admin.theme']->render('@gantry-admin/settings_item.html.twig', $this->params);
    }

    protected function locateParticles() {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];
        $paths = $locator->findResources('gantry-particles://');

        return (new ConfigFileFinder)->listFiles($paths);
    }
}
