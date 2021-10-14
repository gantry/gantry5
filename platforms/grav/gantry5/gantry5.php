<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Gantry\Admin\Router;
use Gantry\Component\Config\Config;
use Gantry\Debugger;
use Gantry\Framework\Assignments;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Request;
use Gantry\Framework\Theme;
use Gantry5\Loader;
use Grav\Common\Config\Config as GravConfig;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Page\Page; // used in new Page()
use Grav\Common\Page\Pages;
use Grav\Common\Page\Types;
use Grav\Common\Plugin;
use Grav\Common\Themes;
use Grav\Common\Twig\Twig;
use Grav\Common\Uri;
use Grav\Common\User\Interfaces\UserInterface;
use Grav\Common\Utils;
use Grav\Events\PermissionsRegisterEvent;
use Grav\Framework\Acl\PermissionsReader;
use Grav\Plugin\Admin\Admin;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use RocketTheme\Toolbox\Session\Message;

/**
 * Class Gantry5Plugin
 */
class Gantry5Plugin extends Plugin
{
    /** @var string */
    public $base;
    /** @var Theme */
    protected $theme;
    /** @var string */
    protected $outline;
    /** @var string */
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
            PermissionsRegisterEvent::class => [
                ['onRegisterPermissions', 100]
            ],
        ];
    }

    /**
     * [PluginsLoadedEvent:100000] Composer autoload.
     *
     * @return ClassLoader
     */
    public function autoload()
    {
        /** @var ClassLoader $loader */
        $loader = require __DIR__ . '/vendor/autoload.php';
        $dev = __DIR__ . '/src/platforms/grav/classes/Gantry';
        if (is_dir($dev)) {
            $loader->addPsr4('Gantry\\', $dev, true);
        }

        return $loader;
    }

    /**
     * @param Event $event
     */
    public function onBeforeCacheClear(Event $event)
    {
        $remove = $event['remove'];
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
        $this->grav['gantry5_plugin'] = $this;
    }

    /**
     * Initialize Gantry admin if in Grav admin.
     */
    public function initializeGantryAdmin()
    {
        /** @var Admin|null $admin */
        $admin = isset($this->grav['admin']) ? $this->grav['admin'] : null;
        if (!($admin && $admin->user->authorize('admin.login'))) {
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

        if ($admin->location !== 'gantry') {
            return;
        }

        // Setup Gantry 5 Framework or throw exception.
        Loader::setup();

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

        if (\GANTRY_DEBUGGER) {
            Debugger::addMessage('Inside Gantry administration');
        }
    }

    /**
     * Initialize administration plugin if admin path matches.
     *
     * Disables system cache.
     */
    public function initializeGantryTheme()
    {
        if (!class_exists(Gantry::class)) {
            return;
        }

        // Setup Gantry 5 Framework or throw exception.
        Loader::setup();

        $gantry = Gantry::instance();

        if (!isset($gantry['theme'])) {
            return;
        }

        /** @var Theme $theme */
        $theme = $gantry['theme'];
        $version = isset($this->grav['theme']->gantry) ? $this->grav['theme']->gantry : 0;

        if (!$gantry->isCompatible($version)) {
            $message = "Theme requires Gantry v{$version} (or later) in order to work! Please upgrade Gantry Framework.";
            if ($this->isAdmin()) {
                /** @var Message $messages */
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

        /** @var Config $global */
        $global = $gantry['global'];
        if ($global->get('asset_timestamps', 1)) {
            $age = $global->get('asset_timestamps_period', 7) * 86400;
            Document::$timestamp_age = $age > 0 ? $age : PHP_INT_MAX;
        } else {
            Document::$timestamp_age = 0;
        }

        // Initialize particle AJAX.
        $this->initializeApi();

        if (\GANTRY_DEBUGGER) {
            Debugger::addMessage("Gantry theme {$theme->name} selected");
        }
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
        /** @var Uri $uri */
        $uri = $this->grav['uri'];

        $apiBase = '/api/particle';
        $route = $uri->route();

        if ($route !== $apiBase && strpos($route, $apiBase . '/') !== 0) {
            return;
        }

        /** @var Pages $pages */
        $pages = $this->grav['pages'];

        $root = rtrim($pages->base(), '/');
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
        $this->grav['page'] = static function() {
            $page = new Page();
            $props = $_REQUEST;
            $outline = !empty($props['outline']) ? $props['outline'] : 'default';
            $id = !empty($props['id']) ? $props['id'] : null;
            unset($props['outline'], $props['id']);

            $page->init(new \SplFileInfo(__DIR__ . '/pages/particle.md'));
            $page->header(
                array_replace((array) $page->header(),
                ['gantry' => ['outline' => $outline], 'particle' => ['id' => $id], 'ajax' => $props])
            );
            $page->slug('particle');

            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage("AJAX request for {$id}");
            }

            return $page;
        };
    }

    /**
     * Add page template types.
     *
     * @param Event $event
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
            'authorize' => ['admin.gantry', 'admin.themes', 'admin.super'],
            'location' => 'gantry',
            'route' => "gantry/configurations/default/layout?nonce={$nonce}",
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
        $page->init(new \SplFileInfo(__DIR__ . '/pages/gantry.md'));
        $page->slug('gantry');

        $gantry = Gantry::instance();

        /** @var Router $router */
        $router = $gantry['router'];
        $response = $router->dispatch();

        // Store response into the page.
        $page->content($response->getBody());

        // Hook page into Grav as current page.
        unset($this->grav['page']);
        $this->grav['page'] = $page;
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

        /** @var Config $global */
        $global = $gantry['global'];

        // Set page to offline.
        if ($global->get('offline', 0)) {
            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage('Site is Offline!');
            }

            /** @var UserInterface $user */
            $user = $this->grav['user'];
            if (empty($user->authenticated && $user->authorize('site.login'))) {
                if (\GANTRY_DEBUGGER) {
                    Debugger::addMessage('Displaying Offline Page');
                }

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
        if (\GANTRY_DEBUGGER) {
            Debugger::addMessage('Displaying Maintenance Page');
        }

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
        /** @var PageInterface $page */
        $page = $this->grav['page'];
        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme = $gantry['theme'];

        $assignments = new Assignments();

        $header = $page->header();
        if (!empty($header->gantry['outline'])) {
            $this->outline = $header->gantry['outline'];
            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage("Current page forces outline {$this->outline} to be used");
            }
        } elseif ($page->name() === 'notfound.md') {
            $this->outline = '_error';
        }

        if (!$this->outline) {
            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage('Selecting outline (rules, matches, scores):');
                Debugger::addMessage($assignments->getPage());
                Debugger::addMessage($assignments->matches());
                Debugger::addMessage($assignments->scores());
            }

            $this->outline = $assignments->select();
        }

        $theme->setLayout($this->outline);
        $this->setPreset();

        if (\GANTRY_DEBUGGER) {
            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];
            Debugger::setLocator($locator);
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
        /** @var PageInterface $page */
        $page = $this->grav['page'];

        if ($page->name() === 'offline.md') {
            $event->page = $page;
            $event->stopPropagation();
        } else {
            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage('Page not found');
            }
            $this->outline = '_error';
        }
    }

    /**
     * Initial stab at registering permissions (WIP)
     *
     * @param PermissionsRegisterEvent $event
     * @return void
     */
    public function onRegisterPermissions(PermissionsRegisterEvent $event): void
    {
        $permissions = $event->permissions;

        $actions = PermissionsReader::fromYaml("plugin://{$this->name}/permissions.yaml");

        $permissions->addActions($actions);
    }

    public function setPreset()
    {
        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme = $gantry['theme'];

        /** @var Request $request */
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
                $this->updateCookie($cookie, '', time() - 42000);
            } else {
                // Update the cookie.
                $this->updateCookie($cookie, $preset, 0);
            }
        } else {
            $preset = $request->cookie[$cookie];
        }

        if ($preset) {
            $theme->setPreset($preset);
            if (\GANTRY_DEBUGGER) {
                $preset = $theme->preset();
                if ($preset) {
                    Debugger::addMessage("Using preset {$preset}");
                }
            }
        }
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expire
     */
    protected function updateCookie($name, $value, $expire = 0)
    {
        /** @var Uri $uri */
        $uri = $this->grav['uri'];

        /** @var GravConfig $config */
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

        /** @var Document $document */
        $document = $gantry['document'];

        // Only filter our streams. If there's an error (bad UTF8), fallback with original output.
        $this->grav->output = $document::urlFilter($this->grav->output, false, 0, true) ?: $this->grav->output;
    }
}
