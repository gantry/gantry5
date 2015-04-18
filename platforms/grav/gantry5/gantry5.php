<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Gantry\Framework\Base\ThemeTrait;
use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Common\Themes;
use Grav\Common\Twig;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Gantry5Plugin extends Plugin
{
    protected $base;
    protected $template;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => [
                ['initialize', 1000],
                ['detectAdmin', 900]
            ]
        ];
    }

    public function initialize()
    {
        /** @var ClassLoader $loader */
        $loader = $this->grav['loader'];
        $loader->addClassMap(['Gantry5\\Loader' => __DIR__ . '/src/Loader.php']);
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

        $base = rtrim($this->grav['base_url'], '/');
        $results = explode('/', $admin->route, 3);
        $theme = array_shift($results);
        $this->template = array_shift($results) ?: 'about';
        $this->route = array_shift($results);
        $this->base =  "{$base}{$admin->base}/{$admin->location}/{$theme}";

        $this->config->set('system.pages.theme', $theme);

        $this->enable([
            'onThemeInitialized' => ['runAdmin', 0],
        ]);
    }

    public function runAdmin()
    {
        $theme = $this->grav['theme'];
        if (!($theme instanceof \Gantry\Framework\Theme)) {
            return;
        }

        $gantry = \Gantry\Framework\Gantry::instance();
        $gantry['base_url'] = $this->base;
        $gantry['routes'] = [
            '1' => '/%s',
            'about' => ''
        ];

        $this->grav['gantry'] = $gantry;

        $this->enable([
            'onPagesInitialized' => ['onPagesInitialized', 900],
            'onTwigInitialized' => ['onTwigInitialized', 900],
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
        $page->init(new \SplFileInfo(__DIR__ . "/pages/gantry5.md"));
        $page->slug($this->template);
        $this->grav['page'] = $page;
    }


    /**
     * Add twig paths to plugin templates.
     */
    public function onTwigInitialized()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];

        /** @var UniformResourceLocator $locator */
        $locator = $this->grav['locator'];
        $locator->addPath('gantry-admin', '', ['user/plugins/gantry5', 'user/plugins/gantry5/common']);
        $locator->addPath('gantry-admin', 'assets', ['user/plugins/gantry5/common']);

        $loader = $twig->loader();
        $loader->setPaths($locator->findResources('gantry-admin://templates'), 'gantry-admin');
    }

    /**
     * Set all twig variables for generating output.
     */
    public function onTwigSiteVariables()
    {
        /** @var Twig $twig */
        $twig = $this->grav['twig'];

        $twig->template = "@gantry-admin/pages/{$this->template}.html.twig";

        $twig->twig_vars['location'] = $this->template;
        $twig->twig_vars['gantry_url'] = $this->base;
    }
}
