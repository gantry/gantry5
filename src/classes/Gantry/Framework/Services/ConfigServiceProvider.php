<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Services;

use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\CompiledConfig;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Framework\Atoms;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(Container $gantry)
    {
        $gantry['blueprints'] = function($c) {
            GANTRY_DEBUGGER && \Gantry\Debugger::startTimer('blueprints', 'Loading blueprints');

            $blueprints = static::blueprints($c);

            GANTRY_DEBUGGER && \Gantry\Debugger::stopTimer('blueprints');

            return $blueprints;
        };

        $gantry['config'] = function($c) {
            // Make sure configuration has been set.
            if (!isset($c['configuration'])) {
                throw new \LogicException('Gantry: Please set current configuration before using $gantry["config"]', 500);
            }

            GANTRY_DEBUGGER && \Gantry\Debugger::startTimer('config', 'Loading configuration');

            // Get the current configuration and lock the value from modification.
            $outline = $c->lock('configuration');

            $config = static::load($c, $outline);

            GANTRY_DEBUGGER && \Gantry\Debugger::setConfig($config)->stopTimer('config');

            return $config;
        };
    }

    public static function blueprints(Container $container)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $container['locator'];

        $cache = $locator->findResource('gantry-cache://theme/compiled/blueprints', true, true);

        $files = [];
        $paths = $locator->findResources('gantry-particles://');
        $files += (new ConfigFileFinder)->setBase('particles')->locateFiles($paths);
        $paths = $locator->findResources('gantry-blueprints://');
        $files += (new ConfigFileFinder)->locateFiles($paths);

        $config = new CompiledBlueprints($cache, $files, GANTRY5_ROOT);

        return $config->load();
    }

    public static function load(Container $container, $name = 'default', $combine = true, $withDefaults = true)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $container['locator'];

        $combine = $combine && $name !== 'default';

        // Merge current configuration with the default.
        $uris = $combine ? ["gantry-config://{$name}", 'gantry-config://default'] : ["gantry-config://{$name}"];

        $paths = [];
        foreach ($uris as $uri) {
            $paths = array_merge($paths, $locator->findResources($uri));
        }

        // Locate all configuration files to be compiled.
        $files = (new ConfigFileFinder)->locateFiles($paths);

        $cache = $locator->findResource('gantry-cache://theme/compiled/config', true, true);

        if (!$cache) {
            throw new \RuntimeException('Who just removed Gantry 5 cache folder? Try reloading the page if it fixes the issue');
        }

        $compiled = new CompiledConfig($cache, $files, GANTRY5_ROOT);
        $compiled->setBlueprints(function() use ($container) {
            return $container['blueprints'];
        });

        $config = $compiled->load($withDefaults);

        // Set atom inheritance.
        $atoms = $config->get('page.head.atoms');
        if (is_array($atoms)) {
            $config->set('page.head.atoms', (new Atoms($atoms))->init()->toArray());
        }

        return $config;
    }
}
