<?php
namespace Grav\Plugin;

use Gantry\Framework\Base\ThemeTrait;
use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Common\Themes;
use Grav\Common\Twig;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class GantryAdminPlugin extends Plugin {
    /**
     * @return array
     */
    public static function getSubscribedEvents() {
        return [
            'onPluginsInitialized' => ['detectAdmin', 900]
        ];
    }

    /**
     * Initialize administration plugin if admin path matches.
     *
     * Disables system cache.
     */
    public function detectAdmin()
    {
        if (!isset($this->grav['admin'])) {
            return;
        }

        /** @var \Grav\Plugin\Admin $admin */
        $admin = $this->grav['admin'];
        if ($admin->location != 'themes' || !$admin->route) {
            return;
        }

        $results = explode('/', $admin->route, 3);
        $theme = array_shift($results);
        $this->template = array_shift($results) ?: 'overview';
        $this->route = array_shift($results);
        $this->base =  "{$admin->base}/{$admin->location}/{$theme}";

        $this->config->set('system.pages.theme', $theme);

        $this->enable([
            'onThemeInitialized' => ['detectTheme', 0],
        ]);
    }

    public function detectTheme()
    {
        $theme = $this->grav['theme'];
        if (!($theme instanceof \Gantry\Framework\Theme)) {
            return;
        }

        $gantry = \Gantry\Framework\Gantry::instance();
        $gantry['base_url'] = $this->base;
        $gantry['routes'] = [
            'overview' => '/overview',
            'settings' => '/settings',
            'page_setup' => '/page_setup',
            'page_setup_edit' => '/page_setup_edit',
            'page_setup_new' => '/page_setup_new',
            'assignments' => '/assignments',
            'updates' => '/updates',
        ];

        $this->grav['gantry'] = $gantry;

        $this->enable([
            'onPagesInitialized' => ['onPagesInitialized', 900],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 900],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 900]
        ]);

    }

    /**
     * Replaces page object with admin one.
     */
    public function onPagesInitialized()
    {
        // Create admin page.
        $page = new Page;
        $page->init(new \SplFileInfo(__DIR__ . "/pages/gantry.md"));
        $page->slug($this->template);
        $this->grav['page'] = $page;

        /** @var UniformResourceLocator $locator */
        $locator = $this->grav['locator'];
        $locator->addPath('gantry-admin', '', 'user/plugins/gantryadmin');
        $locator->addPath('gantry-admin', 'assets', array('user/plugins/gantryadmin/common', 'user/themes/nucleus/common'));
    }


    /**
     * Add twig paths to plugin templates.
     */
    public function onTwigTemplatePaths()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];

        $twig->twig_paths[] = __DIR__ . '/templates';
        $twig->twig_paths[] = __DIR__ . '/common/templates';
    }

    /**
     * Set all twig variables for generating output.
     */
    public function onTwigSiteVariables()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];

        $twig->template = "gantry/{$this->template}.html.twig";

        $twig->twig_vars['location'] = $this->template;
        $twig->twig_vars['gantry_url'] = $this->base;
    }
}
