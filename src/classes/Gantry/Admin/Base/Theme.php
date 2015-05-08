<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Base;

use Gantry\Admin\Particles;
use Gantry\Admin\Styles;
use Gantry\Component\Config\CompiledConfig;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Filesystem\Streams;
use Gantry\Component\Twig\TwigExtension;
use Gantry\Framework\Base\Theme as BaseTheme;
use Gantry\Framework\Platform;
use RocketTheme\Toolbox\StreamWrapper\Stream;
use RocketTheme\Toolbox\StreamWrapper\ReadOnlyStream;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends BaseTheme
{
    public function __construct($path, $name = '')
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        $relpath = Folder::getRelativePath($path);

        // Initialize admin streams.
        /** @var Platform $patform */
        $patform = $gantry['platform'];
        $nucleus = $patform->getEnginePaths('nucleus')[''];
        $patform->set(
            'streams.gantry-admin.prefixes', [
                ''        => ['gantry-theme://admin', $relpath, $relpath . '/common', 'gantry-engine://admin'],
                'assets/' => array_merge([$relpath, $relpath . '/common'], $nucleus, ['gantry-assets://'])
            ]
        );

        $gantry['particles'] = function ($c) {
            return new Particles($c);
        };

        $gantry['styles'] = function ($c) {
            return new Styles($c);
        };

        $gantry['defaults'] = function($c) {
            /** @var UniformResourceLocator $locator */
            $locator = $c['locator'];

            $cache = $locator->findResource('gantry-cache://compiled/config', true, true);
            $paths = $locator->findResources('gantry-config://default');

            $files = (new ConfigFileFinder)->locateFiles($paths);

            $config = new CompiledConfig($cache, $files, function() use ($c) {
                return $c['blueprints'];
            });

            return $config->load();
        };

        parent::__construct($path, $name);

        $this->boot();
    }


    protected function boot()
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var Streams $streams */
        $streams = $gantry['streams'];
        $streams->register();
    }

    public function add_to_context(array $context)
    {
        $context = parent::add_to_context($context);

        return $context;
    }

    public function add_to_twig(\Twig_Environment $twig, \Twig_Loader_Filesystem $loader = null)
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        if (!$loader) {
            $loader = $twig->getLoader();
        }
        $loader->setPaths($locator->findResources('gantry-admin://templates'), 'gantry-admin');

        $twig->addExtension(new \Twig_Extension_Debug());
        $twig->addExtension(new TwigExtension);
        return $twig;
    }

    public function render($file, array $context = array())
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader = new \Twig_Loader_Filesystem($locator->findResources('gantry-admin://templates'));

        $params = array(
            'cache' => $locator->findResource('gantry-cache://twig', true, true),
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => 'html'
        );

        $twig = new \Twig_Environment($loader, $params);

        $this->add_to_twig($twig);

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        return $twig->render($file, $context);
    }
}
