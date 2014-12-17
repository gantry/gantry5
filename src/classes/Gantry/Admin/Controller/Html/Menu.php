<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Menu extends HtmlController
{
    public function index()
    {
        $files = $this->locateParticles();

        $this->params['id'] = $key = 'menu';

        if (!empty($files[$key])) {
            $filename = key($files[$key]);
            $this->params['menu'] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
        }

        return $this->container['admin.theme']->render('@gantry-admin/menu.html.twig', $this->params);
    }

    protected function locateParticles() {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];
        $paths = $locator->findResources('gantry-particles://');

        return (new ConfigFileFinder)->listFiles($paths);
    }
}
