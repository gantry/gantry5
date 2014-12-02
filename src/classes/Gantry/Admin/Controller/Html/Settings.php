<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Settings extends HtmlController
{
    public function index(array $params)
    {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];
        $paths = $locator->findResources('gantry-particles://');
        $files = (new ConfigFileFinder)->locateFiles($paths);

        print_r($files);

        return $this->container['admin.theme']->render('@gantry-admin/settings.html.twig', ['location' => $params['location']]);
    }
}
