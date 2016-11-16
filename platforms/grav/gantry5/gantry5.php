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
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Theme;
use Gantry5\Loader;
use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Common\Themes;
use Grav\Common\Twig\Twig;
use Grav\Common\Utils;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Gantry5Plugin extends Plugin
{
    public $base;

    /**
     * @var Theme
     */
    protected $theme;
    protected $outline;
    protected $link;

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

        include_once __DIR__ . '/Debugger.php';

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

        // If Gantry theme is active, display extra menu item.
        $theme = $this->config->get('system.pages.theme');
        if ($theme && is_file("themes://{$theme}/gantry/theme.yaml")) {
            $this->link = $theme;
            $this->enable([
                'onAdminMenu' => ['onAdminMenu', -10]
            ]);
        }

        /** @var \Grav\Plugin\Admin $admin */
        $admin = $this->grav['admin'];
        $inAdmin = !($admin->location != 'gantry' && ($admin->location != 'themes' || !$admin->route));
        if ($inAdmin) {
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
        $this->base = rtrim("{$base}{$admin->base}/{$admin->location}/{$theme}", '/');

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
        $locator->addPath('gantry-theme', 'images', ["image://{$theme->name}"]);

        $this->theme = $theme;
        if (!$this->isAdmin()) {
            /** @var Platform $patform */
            $patform = $gantry['platform'];

            $nucleus = $patform->getEnginePaths('nucleus')[''];
            $patform->set(
                'streams.gantry-admin.prefixes', [
                    ''        => ['gantry-theme://admin', 'plugins://gantry5/admin', 'plugins://gantry5/admin/common', 'gantry-engine://admin'],
                    'assets/' => array_merge(['plugins://gantry5/admin', 'plugins://gantry5/admin/common'], $nucleus, ['gantry-assets://'])
                ]
            );

            // Add admin paths.
            foreach ($patform->get('streams.gantry-admin.prefixes') as $prefix => $paths) {
                $locator->addPath('gantry-admin', $prefix, $paths);
            }

            $this->enable([
                'onTwigTemplatePaths' => ['onThemeTwigTemplatePaths', 10000],
                'onPageInitialized' => ['onThemePageInitialized', -10000],
                'onTwigExtensions' => ['onThemeTwigInitialized', 0],
                'onTwigSiteVariables' => ['onThemeTwigVariables', 0],
                'onPageNotFound' => ['onPageNotFound', 1000]
            ]);
        }

        if (!$gantry['global']->get('production', 0) || $gantry['global']->get('asset_timestamps', 1)) {
            $age = (int) ($gantry['global']->get('asset_timestamps_period', 7) * 86400);
            Document::$timestamp_age = $age > 0 ? $age : PHP_INT_MAX;
        } else {
            Document::$timestamp_age = 0;
        }

        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Gantry theme {$theme->name} selected");
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
            'onTwigExtensions' => ['onAdminTwigInitialized', 900],
            'onTwigSiteVariables' => ['onAdminTwigVariables', 900]
        ]);

        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage('Inside Gantry administration');
    }

    /**
     * Add navigation item to the admin plugin
     */
    public function onAdminMenu()
    {
        $nonce = Utils::getNonce('gantry-admin');
        $this->grav['twig']->plugins_hooked_nav['Appearance'] = ['route' => "gantry/{$this->link}/configurations/default/styles?nonce={$nonce}", 'icon' => 'fa-gantry'];
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
     * Select outline to be used.
     */
    public function onThemePageInitialized()
    {
        $page = $this->grav['page'];
        $gantry = Gantry::instance();

        /** @var \Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];

        $assignments = new Assignments();

        if (!empty($page->header()->gantry['outline'])) {
            $this->outline = $page->header()->gantry['outline'];
            GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Current page forces outline {$this->outline} to be used");
        }

        if (!$this->outline) {
            if (GANTRY_DEBUGGER) {
                \Gantry\Debugger::addMessage('Selecting outline (rules, matches, scores):');
                \Gantry\Debugger::addMessage($assignments->getPage());
                \Gantry\Debugger::addMessage($assignments->matches());
                \Gantry\Debugger::addMessage($assignments->scores());
            }

            $this->outline = $assignments->select();
        }

        $theme->setLayout($this->outline);
        $this->setPreset();
        
        if (GANTRY_DEBUGGER && method_exists('Gantry\Debugger', 'setLocator')) {
            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];
            \Gantry\Debugger::setLocator($locator);
        }
    }

    /**
     * Initialize nucleus layout engine.
     */
    public function onThemeTwigTemplatePaths()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];
        $twig->twig_paths = array_merge($twig->twig_paths, $this->theme->getTwigPaths());
    }

    /**
     * Initialize nucleus layout engine.
     */
    public function onThemeTwigInitialized()
    {
        /** @var Twig $gravTwig */
        $gravTwig = $this->grav['twig'];
        $twig = $this->theme->renderer();

        $this->theme->extendTwig($twig, $gravTwig->loader());
    }

    /**
     * Load current layout.
     */
    public function onThemeTwigVariables()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];
        $twig->twig_vars += $this->theme->getContext($twig->twig_vars);
    }

    /**
     * Handle non-existing pages.
     */
    public function onPageNotFound()
    {
        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage('Page not found');
        $this->outline = '_error';
    }
    
    public function setPreset()
    {
        $gantry = Gantry::instance();
        $theme = $gantry['theme'];
        $request = $gantry['request'];

        $cookie = md5($theme->name);

        $presetVar = 'presets';
        $resetVar = 'reset-settings';

        if ($request->request[$resetVar] !== null) {
            $preset = false;
        } else {
            $preset = preg_replace('/[^a-z0-9_-]/', '', (string) $request->request[$presetVar]) ?: null;
        }
        if ($preset !== null) {
            if ($preset === false) {
                // Invalidate the cookie.
                $this->updateCookie($cookie, false, time() - 42000);
            } else {
                // Update the cookie.
                $this->updateCookie($cookie, $preset, 0);
            }
        } else {
            $preset = $request->cookie[$cookie];
        }

        if ($preset) {
            $theme->setPreset($preset);
            if (GANTRY_DEBUGGER) {
                $preset = $theme->preset();
                $preset && \Gantry\Debugger::addMessage("Using preset {$preset}");
            }
        }
    }

    protected function updateCookie($name, $value, $expire = 0)
    {
        $uri = $this->grav['uri'];
        $config = $this->grav['config'];

        $path   = $config->get('system.session.path', '/' . ltrim($uri->rootUrl(false), '/'));
        $domain = $uri->host();

        setcookie($name, $value, $expire, $path, $domain);
    }
}
