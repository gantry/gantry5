<?php
namespace Gantry\Component\Config;

use Pimple\Container;
use RocketTheme\Toolbox\DI\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;

class Config implements ServiceProviderInterface
{
    public function register(Container $gantry)
    {
        $gantry['blueprints'] = function($c) {
            /** @var ResourceLocatorInterface $locator */
            $locator = $c['locator'];
            $paths = $locator->findResources('blueprints://config');
            $files = (new ConfigFileFinder)->locateFiles($paths);

            return (new CompiledBlueprints($files))->load();
        };
        $gantry['config'] = function($c) {
            /** @var ResourceLocatorInterface $locator */
            $locator = $c['locator'];
            $paths = $locator->findResources('config://');
            $files = (new ConfigFileFinder)->locateFiles($paths);

            return new CompiledConfig($files, function() use ($c) {
                return $c['blueprints'];
            });
        };
    }
}
