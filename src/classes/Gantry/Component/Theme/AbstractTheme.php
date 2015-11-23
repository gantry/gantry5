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

namespace Gantry\Component\Theme;

use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Twig\TwigExtension;
use Gantry\Framework\Platform;
use Gantry\Framework\Services\ErrorServiceProvider;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

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

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $path;

    /**
     * @var \Twig_Environment
     */
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

        $this->name = $name ? $name : basename($path);
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
        $gantry = static::gantry();

        $context['gantry'] = $gantry;
        $context['theme'] = $this;

        return $context;
    }

    /**
     * Define twig environment.
     *
     * @param \Twig_Environment $twig
     * @param \Twig_Loader_Filesystem $loader
     * @return \Twig_Environment
     */
    public function extendTwig(\Twig_Environment $twig, \Twig_Loader_Filesystem $loader = null)
    {
        if (!$loader) {
            $loader = $twig->getLoader();
        }

        $this->setTwigLoaderPaths($loader);

        $twig->addExtension(new TwigExtension);

        if (method_exists($this, 'toGrid')) {
            $filter = new \Twig_SimpleFilter('toGrid', [$this, 'toGrid']);
            $twig->addFilter($filter);
        }

        return $twig;
    }

    /**
     * Return renderer.
     *
     * @return \Twig_Environment
     */
    public function renderer()
    {
        if (!$this->renderer) {
            $gantry = static::gantry();

            $debug = $gantry->debug();
            $loader = new \Twig_Loader_Filesystem();
            $params = [
                'cache' => $this->getCachePath('twig'),
                'debug' => $debug,
                'auto_reload' => true,
                'autoescape' => 'html'
            ];

            $twig = new \Twig_Environment($loader, $params);

            $this->setTwigLoaderPaths($loader);

            if ($debug) {
                $twig->addExtension(new \Twig_Extension_Debug());
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

        $gantry['file.yaml.cache.path'] = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
    }

    /**
     * Set twig lookup paths to the loader.
     *
     * @param \Twig_Loader_Filesystem $loader
     * @internal
     */
    protected function setTwigLoaderPaths(\Twig_Loader_Filesystem $loader)
    {
        $gantry = static::gantry();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader->setPaths($locator->findResources('gantry-engine://templates'), 'nucleus');
        $loader->setPaths($locator->findResources('gantry-particles://'), 'particles');
    }

    /**
     * Get path to Twig cache.
     *
     * @param string $path
     * @return string
     * @internal
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
    public function add_to_twig(\Twig_Environment $twig, \Twig_Loader_Filesystem $loader = null)
    {
        return $this->extendTwig($twig, $loader);
    }
}
