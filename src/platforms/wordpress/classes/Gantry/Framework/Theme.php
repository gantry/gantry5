<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Theme\AbstractTheme;
use Gantry\Component\Theme\ThemeTrait;
use Gantry\WordPress\Widgets;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Timber\Timber;
use Timber\User;

/**
 * Class Theme
 * @package Gantry\Framework
 */
class Theme extends AbstractTheme
{
    use ThemeTrait;

    /**
     * @var string
     */
    public $url;

    /**
     * @var bool
     * @deprecated 5.1.5
     */
    protected $wordpress = false;

    protected $user;

    /**
     * @param array $context
     * @return array
     */
    public function getContext(array $context)
    {
        $context = parent::getContext($context);

        $gantry = Gantry::instance();

        $context['site'] = $gantry['site'];
        $context['wordpress'] = $gantry['platform'];

        if (!$this->user) {
            $this->user = new User;
        }

        $context['current_user'] = $this->user;

        if (\function_exists('is_rtl')) {
            $context['is_rtl'] = is_rtl();
        }

        return $context;
    }


    /**
     * @see AbstractTheme::extendTwig()
     *
     * @param \Twig_Environment $twig
     * @param \Twig_LoaderInterface $loader
     * @return \Twig_Environment
     */
    public function extendTwig(\Twig_Environment $twig, \Twig_LoaderInterface $loader = null)
    {
        parent::extendTwig($twig, $loader);

        // FIXME: Get timezone from WP.
        //$timezone = 'UTC';
        //$twig->getExtension('Twig_Extension_Core')->setTimezone(new \DateTimeZone($timezone));

        return $twig;
    }

    public function prepare_particles()
    {
        if(!is_admin()) {
            $gantry = Gantry::instance();
            $gantry['theme']->prepare();
        }
    }

    /**
     * Convert all stream uris into proper links.
     */
    public function postProcessOutput($html)
    {
        $gantry = Gantry::instance();

        // Only filter our streams. If there's an error (bad UTF8), fallback with original output.
        return $gantry['document']->urlFilter($html, false, 0, true) ?: $html;
    }

    /**
     * @see AbstractTheme::renderer()
     */
    public function renderer()
    {
        if (!$this->renderer) {
            $twig = parent::renderer();
            $twig = apply_filters('twig_apply_filters', $twig);
            $twig = apply_filters('timber/twig/filters', $twig);
            $twig = apply_filters('timber/twig/functions', $twig);
            $twig = apply_filters('timber/twig/escapers', $twig);
            $twig = apply_filters('timber/loader/twig', $twig);
            $this->renderer = $twig;
        }

        return $this->renderer;
    }

    /**
     * @see AbstractTheme::render()
     *
     * @param string $file
     * @param array $context
     * @return string
     */
    public function render($file, array $context = [])
    {
        static $timberContext;

        if (!isset($timberContext)) {
            $timberContext = Timber::get_context();
        }

        // Include Gantry specific things to the context.
        $context = array_replace($timberContext, $context);

        return $this->renderer()->render($file, $context);
    }

    public function set_template_layout()
    {
        $assignments = new Assignments;
        $selected = $assignments->select();

        if (GANTRY_DEBUGGER) {
            \Gantry\Debugger::addMessage('Selecting outline (rules, matches, scores):', 'debug');
            \Gantry\Debugger::addMessage($assignments->getPage(), 'debug');
            \Gantry\Debugger::addMessage($assignments->matches(), 'debug');
            \Gantry\Debugger::addMessage($assignments->scores(), 'debug');
        }

        $this->setLayout($selected);
    }

    public function widgets_init()
    {
        $gantry = Gantry::instance();

        // Positions are set inside layouts and we need to grab all of them as we do not yet know which layout will be
        // displayed. We also need to register all the positions for the admin.
        $positions = $gantry['outlines']->positions();

        if (!$positions) {
            // No positions are set; display notification in admin.
            add_action('load-widgets.php',
                function() {
                    add_action('admin_notices', function() {
                        echo '<div class="error"><p>' . __('No widget positions have been defined. Please add some in Gantry 5 Layout Manger or read <a target="_blank" rel="noopener" href="http://docs.gantry.org/gantry5/particles/position">documentation</a> on how to create widget positions.', 'gantry5') . '</p></div>';
                    });
                });
        } else {
            foreach ($positions as $name => $title) {
                // We are just registering positions with defaults; there is an event to override chrome based on the
                // template settings. See \Gantry\Wordpress\Widgets for more information.
                register_sidebar([
                    'name'          => __($title, 'gantry5'),
                    'id'            => sanitize_title($name),
                    'before_widget' => '<div id="%1$s" class="widget %2$s">',
                    'after_widget'  => '</div>',
                    'before_title'  => '<h2 class="widgettitle">',
                    'after_title'   => '</h2>',
                ]);
            }
        }
    }

    public function register_menus()
    {
        $gantry = Gantry::instance();

        $menuLocations = $gantry['outlines']->menuLocations();

        if ($menuLocations) {
            register_nav_menus($menuLocations);
        }
    }


    public function url_filter($text)
    {
        $gantry = Gantry::instance();

        // Only filter our streams.
        return $gantry['document']->urlFilter($text, true, 0, true);
    }

    public function register_post_types()
    {
        //this is where you can register custom post types
    }

    public function register_taxonomies()
    {
        //this is where you can register custom taxonomies
    }

    public function disable_wpautop()
    {
        $gantry = Gantry::instance();

        $wpautop = $gantry['config']->get('content.general.wpautop.enabled', '1');

        if($wpautop == '0') {
            remove_filter('the_content', 'wpautop');
            remove_filter('comment_text', 'wpautop');
        }
    }

    public function enqueue_scripts()
    {
        $gantry = Gantry::instance();

        $gantry['document']->registerAssets();
    }

    public function print_styles()
    {
        $styles = Gantry::instance()->styles();
        if ($styles) {
            echo implode("\n    ", $styles) . "\n";
        }
    }

    public function print_scripts()
    {
        $scripts = Gantry::instance()->scripts();
        if ($scripts) {
            echo implode("\n    ", $scripts) . "\n";
        }
    }

    public function print_inline_scripts()
    {
        $gantry = Gantry::instance();

        $gantry['document']->registerScripts('footer');
        $scripts = Gantry::instance()->scripts('footer');
        if ($scripts) {
            echo implode("\n    ", $scripts) . "\n";
        }
    }

    public function preset_styles_init()
    {
        if(!is_admin()) {
            $gantry = Gantry::instance();

            $cookie = md5($this->name);
            $request = $gantry['request'];

            $presetVar = 'presets';
            $resetVar = 'reset-settings';

            if ($request->request[$resetVar] !== null) {
                $preset = false;
            } else {
                $preset = $request->request[$presetVar];
            }

            if ($preset !== null) {
                if ($preset === false) {
                    // Invalidate the cookie.
                    $this->updateCookie($cookie, false, time() - 42000);
                } else {
                    // Update the cookie.
                    $this->updateCookie($cookie, sanitize_html_class($preset), 0);
                }
            } else {
                $preset = $request->cookie[$cookie];
            }

            $preset = ($preset) ? sanitize_html_class($preset) : null;
            $this->setPreset($preset);
        }
    }

    public function preset_styles_update_css()
    {
        $cookie = md5($this->name);

        $this->updateCookie($cookie, false, time() - 42000);
    }

    public function loadposition_shortcode($atts, $content = null)
    {
        extract(shortcode_atts(['id' => ''], $atts));

        $gantry = Gantry::instance();
        $platform = $gantry['platform'];

        return $platform->displayWidgets($id);
    }

    /**
     * @param \Twig_Environment $twig
     * @return \Twig_Environment
     */
    public function timber_loader_twig(\Twig_Environment $twig)
    {
        $twig->enableAutoReload();

        return $twig;
    }

    /**
     * Timber cache location filter.
     *
     * @return string
     */
    public function timber_cache_location()
    {
        return $this->getCachePath('twig');
    }

    /**
     * Extend file type support in WP Theme Editor
     *
     * @param $default_types
     *
     * @return array
     */
    public function extend_theme_editor_filetypes($default_types) {
        $filetypes = [
            'twig',
            'yaml',
            'scss'
        ];

        return $filetypes;
    }

    /**
     * Register menu locations.
     */
    public function register_nav_menus()
    {
        // TODO: Not implemented
        $locations = [];
        foreach ($locations as $key => $val) {
            \register_nav_menu($key, $val);
        }
    }

    public function install()
    {
        $installer = new ThemeInstaller($this->name);
        $installer->installDefaults();
        $installer->finalize();
    }

    /**
     * Get list of twig paths.
     *
     * @return array
     */
    public static function getTwigPaths()
    {
        /** @var UniformResourceLocator $locator */
        $locator = static::gantry()['locator'];

        return $locator->mergeResources(['gantry-theme://views', 'gantry-engine://views']);
    }

    /**
     * @see AbstractTheme::init()
     */
    protected function init()
    {
        parent::init();

        $gantry = Gantry::instance();
        $global = $gantry['global'];

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $installed = is_dir($locator->findResource('gantry-theme://config/default', true, true));
        $linked = is_link($locator->findResource('gantry-theme://'));
        if (!$installed && !$linked) {
            $this->install();
        }

        // Set lookup locations for Timber.
        Timber::$locations = $this->getTwigPaths();

        // Enable caching in Timber.
        Timber::$twig_cache =  (bool) $global->get('compile_twig', 1);
        Timber::$cache = false;

        // Set autoescape in Timber.
        Timber::$autoescape = false;

        add_theme_support('html5', ['comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'widgets']);
        add_theme_support('title-tag');
        add_theme_support('post-formats');
        add_theme_support('post-thumbnails');
        add_theme_support('menus');
        add_theme_support('widgets');

        add_filter('script_loader_tag', ['Gantry\Framework\Document', 'script_add_attributes'], 10, 2);
        add_filter('timber_context', [$this, 'getContext']);
        add_filter('timber/loader/twig', [$this, 'timber_loader_twig']);
        add_filter('timber/cache/location', [$this, 'timber_cache_location']);
        add_filter('timber_compile_result', [$this, 'postProcessOutput']);
        add_filter('wp_theme_editor_filetypes', [$this, 'extend_theme_editor_filetypes']);
        add_filter('timber/twig', [$this, 'extendTwig'], 100);
        add_filter('the_content', [$this, 'url_filter'], 0);
        add_filter('the_excerpt', [$this, 'url_filter'], 0);
        add_filter('widget_text', [$this, 'url_filter'], 0);
        add_filter('widget_content', [$this, 'url_filter'], 0);
        add_filter('widget_text', 'do_shortcode');
        add_filter('widget_content', 'do_shortcode');
        add_filter('widget_update_callback', ['\Gantry\WordPress\Widgets', 'widgetCustomClassesUpdate'], 10, 4);
        add_filter('dynamic_sidebar_params', ['\Gantry\WordPress\Widgets', 'widgetCustomClassesSidebarParams'], 9);

        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('init', [$this, 'register_menus']);

//        add_action('after_setup_theme', [$this, 'register_nav_menus']);
        add_action('template_redirect', [$this, 'set_template_layout'], -10000);
        add_action('template_redirect', [$this, 'disable_wpautop'], 10000);
        add_action('widgets_init', [$this, 'widgets_init']);
        add_action('wp_enqueue_scripts', [$this, 'prepare_particles'], 15);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts'], 20);
        add_action('wp_head', [$this, 'print_styles'], 20);
        add_action('wp_head', [$this, 'print_scripts'], 30);
        add_action('admin_print_styles', [$this, 'print_styles'], 200);
        add_action('admin_print_scripts', [$this, 'print_scripts'], 200);
        add_action('wp_footer', [$this, 'print_inline_scripts'], 100);
        add_action('in_widget_form', ['\Gantry\WordPress\Widgets', 'widgetCustomClassesForm'], 10, 3);
        add_action('widgets_init', function() {
            register_widget('\Gantry\WordPress\Widget\Particle');
        });

        // Particle AJAX actions.
        add_action('wp_ajax_particle', [$this, 'ajax_particle']);
        add_action('wp_ajax_nopriv_particle', [$this, 'ajax_particle']);

        add_shortcode('loadposition', [$this, 'loadposition_shortcode']);

        // Offline support.
        add_action('init', function() use ($gantry, $global) {
            global $pagenow;
            if ($global->get('offline')) {
                if (!(is_super_admin() || current_user_can('manage_options') || $pagenow == 'wp-login.php')) {
                    if (locate_template(['offline.php'])) {
                        add_filter('template_include', function () {
                            return locate_template(['offline.php']);
                        });
                    } else {
                        wp_die($global->get('offline_message'), get_bloginfo('title'));
                    }
                } else {
                    $gantry['messages']->add(__('Site is currently in offline mode.', 'gantry5'), 'warning');
                }
            }
        });

        $this->preset_styles_init();

        // Load theme text domains
        $domain = $this->details()->get('configuration.theme.textdomain', $this->name);
        load_theme_textdomain($domain, $this->path . '/languages');

        $this->url = $gantry['site']->theme->link;

        $gantry['configuration'] = 'default';

        $gantry->fireEvent('theme.init');
    }

    /**
     * @see AbstractTheme::setTwigLoaderPaths()
     *
     * @param \Twig_LoaderInterface $loader
     * @return \Twig_Loader_Filesystem
     */
    protected function setTwigLoaderPaths(\Twig_LoaderInterface $loader)
    {
        $loader = parent::setTwigLoaderPaths($loader);
        
        if ($loader) {
            // TODO: right now we are replacing all paths; we need to do better, but there are some issues with this call.
            $loader->setPaths($this->getTwigPaths());
        }

        return $loader;
    }

    protected function updateCookie($name, $value, $expire = 0)
    {
        $path   = SITECOOKIEPATH;
        $domain = COOKIE_DOMAIN;

        setcookie($name, $value, $expire, $path, $domain);
    }

    /**
     * Serve particle AJAX requests in '/wp-admin/admin-ajax.php?action=particle'.
     */
    public function ajax_particle()
    {
        $format = !empty($_GET['format']) ? sanitize_key($_GET['format']) : 'html';
        $outline = !empty($_GET['outline']) ? sanitize_key($_GET['outline']) : 'default';
        $identifier = !empty($_GET['id']) ? sanitize_key($_GET['id']) : null;

        if (!in_array($format, ['json', 'raw'], true)) {
            $this->ajax_not_found($format);
        }

        $props = $_GET;
        unset($props['action'], $props['outline'], $props['id'], $props['format']);

        $gantry = \Gantry\Framework\Gantry::instance();

        $this->setLayout($outline, true);

        if ($identifier === 'main-particle') {
            // Does not exist in WP.
            $this->ajax_not_found($format);
        } elseif (preg_match('`^(.*?)-widget-(.*?)-([\d]+)$`', $identifier, $matches)) {
            // Render widget.
            $sidebar = $matches[1];
            $type = $matches[2];
            $id = 'particle_widget-' . $matches[3];
            $html = Widgets::getAjax($sidebar, $id, $props);

            if ($html === null) {
                $this->ajax_not_found($format);
            }

            $this->ajax_particle_output('widget.' . $type, $identifier, $props, $html, $format);
        } else {
            // Render particle.
            $layout = $this->loadLayout();
            $particle = $layout->find($identifier);
            if (!isset($particle->type) || $particle->type !== 'particle') {
                $this->ajax_not_found($format);
            }

            $context = array(
                'gantry' => $gantry,
                'inContent' => false,
                'ajax' => $props,
            );

            $block = $this->getContent($particle, $context);
            $type = $particle->type . '.' . $particle->subtype;
            $html = (string) $block;
            $this->ajax_particle_output($type, $identifier, $props, $html, $format);
        }
    }

    protected function ajax_particle_output($type, $identifier, $props, $html, $format)
    {
        ob_clean();

        if ($format === 'raw') {
            echo $html;
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'code' => 200,
                'type' => $type,
                'id' => $identifier,
                'props' => (object)$props,
                'html' => $html
            ]);
        }

        wp_die();
    }

    protected function ajax_not_found($format)
    {
        ob_clean();

        if ($format === 'raw') {
            echo 'Not Found';
        } else {
            header('Content-Type: "application/json; charset=utf-8"');
            echo json_encode([
                'code' => 404,
                'message' => 'Not Found'
            ]);
        }

        wp_die();
    }

    /**
     * @param  bool $enable
     * @return bool
     * @deprecated 5.1.5
     */
    public function wordpress($enable = null)
    {
        if ($enable) {
            $this->wordpress = (bool) $enable;
        }

        return $this->wordpress;
    }
}
