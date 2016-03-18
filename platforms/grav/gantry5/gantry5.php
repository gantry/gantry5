<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Gantry\Admin\Router;
use Gantry\Component\Filesystem\Streams;
use Gantry\Framework\Assignments;
use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Gantry5\Loader;
use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Common\Themes;
use Grav\Common\Twig\Twig;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Gantry5Plugin extends Plugin
{
    public $base;
    protected $template;

    /**
     * @var Theme
     */
    protected $theme;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => [
                ['initialize', 1000],
                ['initializeGantryAdmin', -100]
            ],
            'onThemeInitialized' => [
                ['initializeGantryTheme', -20]
            ],
            'onAdminMenu' => [
                ['onAdminMenu', -10]
            ],
        ];
    }

    /**
     * Bootstrap Gantry loader.
     */
    public function initialize()
    {
        /** @var ClassLoader $loader */
        $loader = $this->grav['loader'];
        $loader->addClassMap(['Gantry5\\Loader' => __DIR__ . '/src/Loader.php']);

        $this->grav['gantry5_plugin'] = $this;
    }

    /**
     * Initialize Gantry admin if in Grav admin.
     */
    public function initializeGantryAdmin()
    {
        if (!$this->isAdmin()) {
            return;
        }

        $this->enable([
            'onAdminMenu' => ['onAdminMenu', 0]
        ]);

        /** @var \Grav\Plugin\Admin $admin */
        $admin = $this->grav['admin'];
        $inAdmin = !($admin->location != 'gantry' && ($admin->location != 'themes' || !$admin->route));

        if (!$inAdmin) {
            $theme = $this->config->get('system.pages.theme');
        } else {
            $results = explode('/', $admin->route, 2);
            $theme = array_shift($results);
        }

        // Do not initialize Gantry on non-Gantry themes.
        if ($theme && !is_file("themes://{$theme}/gantry/theme.yaml")) {
            return;
        }

        // Setup Gantry 5 Framework or throw exception.
        \Gantry5\Loader::setup();

        if (!defined('GANTRYADMIN_PATH')) {
            define('GANTRYADMIN_PATH', 'plugins://gantry5/admin');
        }

        $base = rtrim($this->grav['base_url'], '/');
        $this->base =  rtrim("{$base}{$admin->base}/{$admin->location}/{$theme}", '/');

        if (!$inAdmin) {
            $this->enable([
                'onAdminThemeInitialized' => ['initAdmin', 0]
            ]);
            return;
        }

        $this->enable([
            'onAdminThemeInitialized' => ['runAdmin', 0]
        ]);

        if ($theme) {
            // Switch theme and initialize it.
            $this->config->set('system.pages.theme', $theme);
        }
    }

    /**
     * Initialize administration plugin if admin path matches.
     *
     * Disables system cache.
     */
    public function initializeGantryTheme()
    {
        if (!class_exists('Gantry\Framework\Gantry')) {
            return;
        }

        $gantry = Gantry::instance();

        if (!isset($gantry['theme'])) {
            return;
        }

        /** @var \Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];
        $version = isset($this->grav['theme']->gantry) ? $this->grav['theme']->gantry : 0;

        if (!$gantry->isCompatible($version)) {
            $message = "Theme requires Gantry v{$version} (or later) in order to work! Please upgrade Gantry Framework.";
            if ($this->isAdmin()) {
                $messages = $this->grav['messages'];
                $messages->add($message, 'error');
                return;
            } else {
                throw new \LogicException($message);
            }
        }

        $theme->registerStream("user://gantry5/themes/{$theme->name}");

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $locator->resetScheme('theme')->addPath('theme', '', 'gantry-theme://');
        $locator->addPath('theme', 'blueprints', ['gantry-theme://blueprints', 'gantry-engine://blueprints/pages']);

        $this->theme = $theme;

        if (!$this->isAdmin()) {
            $this->enable([
                'onPagesInitialized' => ['onThemePagesInitialized', 0],
                'onTwigInitialized' => ['onThemeTwigInitialized', 0],
                'onTwigSiteVariables' => ['onThemeTwigVariables', 0]
            ]);
        }
   }

    public function initAdmin()
    {
        /** @var Themes $themes */
        $themes = $this->grav['themes'];
        $themes->initTheme();

        $gantry = Gantry::instance();
        $gantry['base_url'] = $this->base;

        $this->grav['gantry5'] = $gantry;
    }

    public function runAdmin()
    {
        $this->initAdmin();

        $this->grav['gantry5']['router'] = function ($c) {
            return new Router($c);
        };

        $this->enable([
            'onPagesInitialized' => ['onAdminPagesInitialized', 900],
            'onTwigInitialized' => ['onAdminTwigInitialized', 900],
            'onTwigSiteVariables' => ['onAdminTwigVariables', 900]
        ]);
    }

    /**
     * Add navigation item to the admin plugin
     */
    public function onAdminMenu()
    {
        $this->grav['twig']->plugins_hooked_nav['Gantry'] = ['route' => 'gantry', 'icon' => 'fa-gantry'];
    }

    /**
     * Replaces page object with admin one.
     */
    public function onAdminPagesInitialized()
    {
        // Create admin page.
        $page = new Page;
        $page->init(new \SplFileInfo(__DIR__ . "/pages/gantry.md"));
        $page->slug('gantry');

        // Dispatch Gantry in output buffer.
        ob_start();
        $gantry = Gantry::instance();
        $gantry['router']->dispatch();
        $content = ob_get_clean();

        // Store response into the page.
        $page->content($content);

        // Hook page into Grav as current page.
        unset( $this->grav['page']);
        $this->grav['page'] = function () use ($page) { return $page; };
    }

    /**
     * Add twig paths to plugin templates.
     */
    public function onAdminTwigInitialized()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];

        /** @var UniformResourceLocator $locator */
        $locator = $this->grav['locator'];

        $loader = $twig->loader();
        $loader->prependPath($locator->findResource('plugins://gantry5/templates'));
    }

    /**
     * Set all twig variables for generating output.
     */
    public function onAdminTwigVariables()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];

        $twig->twig_vars['gantry_url'] = $this->base;
    }

    /**
     * Replaces page object with admin one.
     */
    public function onThemePagesInitialized()
    {
        $gantry = Gantry::instance();

        /** @var \Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];

        $assignments = new Assignments();

        $theme->setLayout($assignments->select());
    }

    /**
     * Initialize nucleus layout engine.
     */
    public function onThemeTwigInitialized()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];
        $this->theme->extendTwig($twig->twig(), $twig->loader());
    }

    /**
     * Load current layout.
     */
    public function onThemeTwigVariables()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];
        $twig->twig_vars = $this->theme->getContext($twig->twig_vars);
    }
}
