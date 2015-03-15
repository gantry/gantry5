<?php
namespace Gantry\Component\Config;

use Pimple\Container;
use RocketTheme\Toolbox\DI\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(Container $gantry)
    {
        $gantry['blueprints'] = function($c) {
            /** @var ResourceLocatorInterface $locator */
            $locator = $c['locator'];

            $cache = $locator->findResource('gantry-cache://compiled/blueprints', true, true);
            $paths = $locator->findResources('gantry-blueprints://config');
            $files = (new ConfigFileFinder)->locateFiles($paths);

            $config = new CompiledBlueprints($cache, $files);

            return $config->load();
        };
        $gantry['config'] = function($c) {
            /** @var ResourceLocatorInterface $locator */
            $locator = $c['locator'];

            $cache = $locator->findResource('gantry-cache://compiled/config', true, true);

            // Make sure configuration has been set.
            if (!isset($c['configuration'])) {
                throw new \LogicException('Gantry: Please set current configuration before using $gantry["config"]', 500);
            }

            // Get the current configuration and lock the value from modification.
            $configuration = $c->lock('configuration');

            // Merge current configuration with the default.
            $uris = array_unique(["gantry-config://{$configuration}", 'gantry-config://default']);

            $paths = [];
            foreach ($uris as $uri) {
                $paths = array_merge($paths, $locator->findResources($uri));
            }

            // Locate all configuration files to be compiled.
            $files = (new ConfigFileFinder)->locateFiles($paths);

            $config = new CompiledConfig($cache, $files, function() use ($c) {
                return $c['blueprints'];
            });

            return $config->load();
        };
    }
}
