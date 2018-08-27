<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Gantry\Admin\Router;
use Gantry\Framework\Assignments;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Theme;
use Grav\Common\Page\Page;
use Grav\Common\Page\Types;
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
    protected $apiPath;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onBeforeCacheClear' => [
                ['onBeforeCacheClear', 0],
            ],
            'onPluginsInitialized' => [
                ['initialize', 1000],
                ['initializeGantryAdmin', -100]
            ],
            'onThemeInitialized' => [
                ['initializeGantryTheme', -20]
            ],
            'onDataTypeExcludeFromDataManagerPluginHook' => [
                ['onDataTypeExcludeFromDataManagerPluginHook', 0],
            ],
        ];
    }

    public function onBeforeCacheClear(Event $event)
    {
        // TODO: remove BC to 1.1.9-rc.3
        $remove = isset($event['remove']) ? $event['remove'] : 'standard';
        $paths = $event['paths'];

        if (in_array($remove, ['all', 'standard', 'cache-only'], true) && !in_array('cache://', $paths, true)) {
            $paths[] = 'cache://gantry5/';
            $event['paths'] = $paths;
        }
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

        // If Gantry theme is active, display extra menu item and make sure that page types get loaded.
        $theme = $this->config->get('system.pages.theme');
        if ($theme && is_file("themes://{$theme}/gantry/theme.yaml")) {
            $enabled = true;
            $this->enable([
                'onGetPageTemplates' => ['onGetPageTemplates', -10],
                'onAdminMenu' => ['onAdminMenu', -10],
                'onAdminThemeInitialized' => ['initAdminTheme', 0]
            ]);
        }

        /** @var \Grav\Plugin\Admin $admin */
        $admin = $this->grav['admin'];
        $inAdmin = $admin->location === 'gantry';
        if (!$inAdmin) {
            return;
        }

        // Setup Gantry 5 Framework or throw exception.
        \Gantry5\Loader::setup();

        if (!defined('GANTRYADMIN_PATH')) {
            define('GANTRYADMIN_PATH', 'plugins://gantry5/admin');
        }

        $base = rtrim($this->grav['base_url_relative'], '/');
        $this->base = rtrim("{$base}{$admin->base}/{$admin->location}", '/');

        $gantry = Gantry::instance();
        $gantry['base_url'] = $this->base;
        $gantry['router'] = new Router($gantry);
        $gantry['router']->boot();

        $this->enable([
            'onPagesInitialized' => ['onAdminPagesInitialized', 900],
            'onTwigExtensions' => ['onAdminTwigInitialized', 900],
            'onTwigSiteVariables' => ['onAdminTwigVariables', 900]
        ]);

        if (empty($enabled)) {
            $this->enable([
            'onAdminThemeInitialized' => ['initAdminTheme', 0]
        ]);
        }

        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage('Inside Gantry administration');
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
            }

            throw new \LogicException($message);
        }

        $theme->registerStream(
            [
                "user://data/gantry5/themes/{$theme->name}",
                "user://gantry5/themes/{$theme->name}", // TODO: remove
            ]
        );

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $locator->resetScheme('theme')->addPath('theme', '', 'gantry-theme://');
        $locator->addPath('theme', 'blueprints', ['gantry-theme://blueprints', 'gantry-engine://blueprints/pages']);
        $locator->addPath('gantry-theme', 'images', ["image://{$theme->name}"]);

        $this->theme = $theme;
        if (!$this->isAdmin()) {
            /** @var Platform $platform */
            $platform = $gantry['platform'];

            $nucleus = $platform->getEnginePaths('nucleus')[''];
            $platform->set(
                'streams.gantry-admin.prefixes', [
                    ''        => ['gantry-theme://admin', 'plugins://gantry5/admin', 'plugins://gantry5/admin/common', 'gantry-engine://admin'],
                    'assets/' => array_merge(['plugins://gantry5/admin', 'plugins://gantry5/admin/common'], $nucleus, ['gantry-assets://'])
                ]
            );

            // Add admin paths.
            foreach ($platform->get('streams.gantry-admin.prefixes') as $prefix => $paths) {
                $locator->addPath('gantry-admin', $prefix, $paths);
            }

            $this->enable([
                'onTwigTemplatePaths' => ['onThemeTwigTemplatePaths', 10000],
                'onPagesInitialized' => ['onThemePagesInitialized', 100000],
                'onPageInitialized' => ['onThemePageInitialized', -10000],
                'getMaintenancePage' => ['getMaintenancePage', 0],
                'onTwigExtensions' => ['onThemeTwigInitialized', 0],
                'onTwigSiteVariables' => ['onThemeTwigVariables', 0],
                'onPageNotFound' => ['onPageNotFound', 1000],
                'onOutputGenerated' => ['onOutputGenerated', 0],
            ]);
        }

        if (!$gantry['global']->get('production', 0) || $gantry['global']->get('asset_timestamps', 1)) {
            $age = (int) ($gantry['global']->get('asset_timestamps_period', 7) * 86400);
            Document::$timestamp_age = $age > 0 ? $age : PHP_INT_MAX;
        } else {
            Document::$timestamp_age = 0;
        }

        // Initialize particle AJAX.
        $this->initializeApi();

        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Gantry theme {$theme->name} selected");
   }

    public function initAdminTheme()
    {
        /** @var Themes $themes */
        $themes = $this->grav['themes'];
        $themes->initTheme();

        $gantry = Gantry::instance();

        $this->grav['gantry5'] = $gantry;
    }

    /**
     * Serve particle AJAX requests in '/api/particles'.
     */
    public function initializeApi()
    {
        $apiBase = '/api/particle';
        $route = $this->grav['uri']->route();

        if ($route !== $apiBase || !substr($route, 0, strlen($apiBase) + 1) === $apiBase . '/') {
            return;
        }

        $root = rtrim($this->grav['pages']->base(), '/');
        $this->apiPath = substr($route, strlen($root . $apiBase) + 1);

        $this->enable([
            'onPagesInitialized' => ['initializeParticleAjax', -9999]
        ]);
    }

    /**
     * Initialize Widgets page.
     */
    public function initializeParticleAjax()
    {
        // make sure page is not frozen!
        unset($this->grav['page']);

        // Replace page service with a widget.
        $this->grav['page'] = function () {
            $page = new Page;
            $props = $_REQUEST;
            $outline = !empty($props['outline']) ? $props['outline'] : 'default';
            $id = !empty($props['id']) ? $props['id'] : null;
            unset($props['outline'], $props['id']);

            $page->init(new \SplFileInfo(__DIR__ . "/pages/particle.md"));
            $page->header(
                array_replace((array) $page->header(),
                ['gantry' => ['outline' => $outline], 'particle' => ['id' => $id], 'ajax' => $props])
            );
            $page->slug('particle');

            GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("AJAX request for {$id}");

            return $page;
        };
    }

    /**
     * Add page template types.
     *
     * @since 5.4.3
     */
    public function onGetPageTemplates(Event $event)
    {
        /** @var Types $types */
        $types = $event->types;
        $types->scanTemplates('gantry-engine://templates');
    }

    /**
     * Add navigation item to the admin plugin
     */
    public function onAdminMenu()
    {
        $nonce = Utils::getNonce('gantry-admin');
        $this->grav['twig']->plugins_hooked_nav['Gantry 5'] = [
            'authorize' => 'admin.gantry',
            'location' => 'gantry',
            'route' => "gantry/configurations/default/styles?nonce={$nonce}",
            'icon' => 'fa-gantry'
        ];
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
     * @param Event $event
     */
    public function onThemePagesInitialized(Event $event)
    {
        $gantry = Gantry::instance();
        
        // Set page to offline.
        if ($gantry['global']->get('offline', 0)) {
            GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Site is Offline!");

            $user = $this->grav['user'];
            if (empty($user->authenticated && $user->authorize('site.login'))) {
                GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Displaying Offline Page");

                $page = new Page;
                $page->init(new \SplFileInfo(__DIR__ . '/pages/offline.md'));

                unset($this->grav['page']);
                $this->grav['page'] = $page;

                $this->enable([
                    'onMaintenancePage' => ['onMaintenancePage', 100000],
                ]);

                // Site is offline, there is nothing else to do.
                $event->stopPropagation();
            }
        }
    }

    /**
     * @param Event $event
     */
    public function getMaintenancePage(Event $event)
    {
        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Displaying Maintenance Page");

        $page = new Page;
        $page->init(new \SplFileInfo(__DIR__ . '/pages/offline.md'));

        $event->page = $page;

        $this->enable([
            'onMaintenancePage' => ['onThemePageInitialized', 0],
        ]);

        $event->stopPropagation();
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

        $header = $page->header();
        if (!empty($header->gantry['outline'])) {
            $this->outline = $header->gantry['outline'];
            GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Current page forces outline {$this->outline} to be used");
        } elseif ($page->name() === 'notfound.md') {
            $this->outline = '_error';
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
        $this->theme->renderer();
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
    public function onPageNotFound(Event $event)
    {
        $page = $this->grav['page'];
        if ($page->name() === 'offline.md') {
            $event->page = $page;
            $event->stopPropagation();
        } else {
            GANTRY_DEBUGGER && \Gantry\Debugger::addMessage('Page not found');
            $this->outline = '_error';
        }
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

    public function onDataTypeExcludeFromDataManagerPluginHook()
    {
        $this->grav['admin']->dataTypesExcludedFromDataManagerPlugin[] = 'gantry5';
    }

    public function onOutputGenerated()
    {
        $gantry = Gantry::instance();

        // Only filter our streams. If there's an error (bad UTF8), fallback with original output.
        $this->grav->output = $gantry['document']->urlFilter($this->grav->output, false, 0, true) ?: $this->grav->output;
    }
}
