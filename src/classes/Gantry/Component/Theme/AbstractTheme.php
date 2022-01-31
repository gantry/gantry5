<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Theme;

use Gantry\Component\Config\Config;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Twig\TwigCacheFilesystem;
use Gantry\Component\Twig\TwigExtension;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Services\ErrorServiceProvider;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\TwigFilter;

/**
 * Class AbstractTheme
 * @package Gantry\Component
 *
 * @property string $path
 * @property string $layout
 */
abstract class AbstractTheme
{
    use GantryTrait;

    /** @var string */
    public $name;
    /** @var string */
    public $path;

    /** @var Environment|null */
    protected $renderer;

    /**
     * Construct theme object.
     *
     * @param string $path
     * @param string $name
     */
    public function __construct($path, $name = null)
    {
        if (!is_dir($path)) {
            throw new \LogicException('Theme not found!');
        }

        $this->name = $name ?: Gantry::basename($path);
        $this->path = $path;

        $this->init();
    }

    /**
     * Get context for render().
     *
     * @param array $context
     * @return array
     */
    public function getContext(array $context)
    {
        $context['theme'] = $this;

        return $context;
    }

    /**
     * Define twig environment.
     *
     * @param Environment $twig
     * @param LoaderInterface $loader
     * @return Environment
     */
    public function extendTwig(Environment $twig, LoaderInterface $loader = null)
    {
        if ($twig->hasExtension(TwigExtension::class)) {
            return $twig;
        }

        if (!$loader) {
            $loader = $twig->getLoader();
        }

        $this->setTwigLoaderPaths($loader);

        $twig->addExtension(new TwigExtension);

        if (method_exists($this, 'toGrid')) {
            $filter = new TwigFilter('toGrid', [$this, 'toGrid']);
            $twig->addFilter($filter);
        }

        return $twig;
    }

    /**
     * Return renderer.
     *
     * @return Environment
     */
    public function renderer()
    {
        if (!$this->renderer) {
            $gantry = static::gantry();

            /** @var Config $global */
            $global = $gantry['global'];

            $cachePath = $global->get('compile_twig', 1) ? $this->getCachePath('twig') : null;
            if ($cachePath) {
                /** @phpstan-ignore-next-line */
                if (Environment::VERSION_ID > 3) {
                    // Twig 3 support.
                    $cache = new FilesystemCache($cachePath, FilesystemCache::FORCE_BYTECODE_INVALIDATION);
                /** @phpstan-ignore-next-line */
                } else {
                    $cache = new TwigCacheFilesystem($cachePath, FilesystemCache::FORCE_BYTECODE_INVALIDATION);
                }
            } else {
                $cache = null;
            }
            $debug = $gantry->debug();
            $production = (bool) $global->get('production', 1);
            $loader = new FilesystemLoader();
            $params = [
                'cache' => $cache,
                'debug' => $debug,
                'auto_reload' => !$production,
                'autoescape' => 'html'
            ];

            $twig = new Environment($loader, $params);

            $this->setTwigLoaderPaths($loader);

            if ($debug) {
                $twig->addExtension(new DebugExtension());
            }

            $this->renderer = $this->extendTwig($twig, $loader);
        }

        return $this->renderer;
    }

    /**
     * Render a template file by using given context.
     *
     * @param string $file
     * @param array $context
     * @return string
     */
    public function render($file, array $context = [])
    {
        // Include Gantry specific things to the context.
        $context = $this->getContext($context);

        return $this->renderer()->render($file, $context);
    }

    /**
     * Compile and render twig string.
     *
     * @param string $string
     * @param array $context
     * @return string
     */
    public function compile($string, array $context = [])
    {
        $renderer = $this->renderer();
        $template = $renderer->createTemplate($string);

        // Include Gantry specific things to the context.
        $context = $this->getContext($context);

        return $template->render($context);
    }

    /**
     * Initialize theme.
     */
    protected function init()
    {
        $gantry = static::gantry();
        $gantry['streams']->register();

        // Only add error service if development or debug mode has been enabled or user is admin.
        if (!$gantry['global']->get('production', 0) || $gantry->debug() || $gantry->admin()) {
            $gantry->register(new ErrorServiceProvider);
        }

        // Initialize theme cache stream.
        $cachePath = $this->getCachePath();

        Folder::create($cachePath);

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $locator->addPath('gantry-cache', 'theme', [$cachePath], true, true);

        CompiledYamlFile::$defaultCachePath = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
        CompiledYamlFile::$defaultCaching = $gantry['global']->get('compile_yaml', 1);
    }

    /**
     * Set twig lookup paths to the loader.
     *
     * @param LoaderInterface $loader
     * @return FilesystemLoader|null
     * @internal
     */
    protected function setTwigLoaderPaths(LoaderInterface $loader)
    {
        if ($loader instanceof ChainLoader) {
            $new = new FilesystemLoader();
            $loader->addLoader($new);
            $loader = $new;
        } elseif (!($loader instanceof FilesystemLoader)) {
            return null;
        }

        $gantry = static::gantry();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader->setPaths($locator->findResources('gantry-engine://templates'), 'nucleus');
        $loader->setPaths($locator->findResources('gantry-particles://'), 'particles');

        return $loader;
    }

    /**
     * Get path to Twig cache.
     *
     * @param string $path
     * @return string
     */
    protected function getCachePath($path = '')
    {
        $gantry = static::gantry();

        /** @var Platform $patform */
        $patform = $gantry['platform'];

        // Initialize theme cache stream.
        return $patform->getCachePath() . '/' . $this->name . ($path ? '/' . $path : '');
    }

    /**
     * @deprecated 5.0.2
     */
    public function debug()
    {
        return static::gantry()->debug();
    }

    /**
     * @deprecated 5.1.5
     */
    public function add_to_context(array $context)
    {
        return $this->getContext($context);
    }

    /**
     * @deprecated 5.1.5
     */
    public function add_to_twig(Environment $twig, LoaderInterface $loader = null)
    {
        return $this->extendTwig($twig, $loader);
    }
}
