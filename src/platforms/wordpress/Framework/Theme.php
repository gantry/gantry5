<?php
namespace Gantry\Framework;

use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends Base\Theme
{
    public $path;
    protected $wordpress = false;
    protected $user;

    public function __construct( $path, $name = '' )
    {
        global $pagenow;

        parent::__construct($path, $name);

        $gantry = Gantry::instance();
        $global = $gantry['global'];

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        \Timber::$locations = $locator->findResources('gantry-engine://views');

        add_theme_support( 'html5', [ 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'widgets' ] );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-formats' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'menus' );
        add_theme_support( 'widgets' );
        add_filter( 'timber_context', [ $this, 'add_to_context' ] );
        add_filter( 'get_twig', [ $this, 'add_to_twig' ] );
        add_action( 'template_redirect', [ $this, 'set_template_layout' ], -10000 );
        add_action( 'init', [ $this, 'register_post_types' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
        add_action( 'widgets_init', [ $this, 'widgets_init' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_head', [ $this, 'print_styles' ], 20 );
        add_action( 'wp_head', [ $this, 'print_scripts' ], 30 );
        add_action( 'admin_print_styles', [ $this, 'print_styles' ], 200 );
        add_action( 'admin_print_scripts', [ $this, 'print_scripts' ], 200 );
        add_action( 'wp_footer', [ $this, 'print_inline_scripts' ] );

        // Offline support.
        $global = $gantry['global'];
        if ($global->get('offline') && !is_super_admin() && !current_user_can('manage_options')
            && $pagenow != 'wp-login.php') {
            if (locate_template(['offline.php'])) {
                add_filter('template_include', function () {
                    return locate_template(['offline.php']);
                });
            } else {
                wp_die($global->get('offline_message'), get_bloginfo('title'));
            }
        }
    }

    public function init()
    {
        parent::init();

        $this->preset_styles_init();

        // Load theme text domains
        $domain = $this->details()->get('configuration.theme.textdomain', $this->name);
        load_theme_textdomain( $domain, $this->path . '/languages' );
    }

    /**
     * @deprecated 5.0.2
     */
    public function debug()
    {
        return Gantry::instance()->debug();
    }

    public function renderer()
    {
        if (!$this->renderer) {
            $gantry = Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $loader = new \Twig_Loader_Filesystem(\Timber::$locations);

            $params = array(
                'cache' => $locator->findResource('gantry-cache://theme/twig', true, true),
                'debug' => $gantry->debug(),
                'auto_reload' => true,
                'autoescape' => 'html'
            );

            $twig = new \Twig_Environment($loader, $params);

            if ($gantry->debug()) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            $twig = apply_filters('twig_apply_filters', $twig);
            $twig = apply_filters('timber/twig/filters', $twig);
            $twig = apply_filters('timber/loader/twig', $twig);

            // FIXME: Get timezone from WP.
            //$timezone = 'UTC';
            //$twig->getExtension('core')->setTimezone(new \DateTimeZone($timezone));

            $this->add_to_twig($twig);

            $this->renderer = $twig;
        }

        return $this->renderer;
    }


    public function render($file, array $context = [])
    {
        static $timberContext;

        if (!isset($timberContext)) {
            $timberContext = \Timber::get_context();
        }

        // Include Gantry specific things to the context.
        $context = array_replace($timberContext, $context);

        return $this->renderer()->render($file, $context);
    }

    public function set_template_layout()
    {
        $gantry = Gantry::instance();

        $chooser = new OutlineChooser;

        /** @var Theme $theme */
        $theme = $gantry[ 'theme' ];
        $theme->setLayout( $chooser->select() );
    }

    public function widgets_init()
    {
        $gantry = Gantry::instance();

        // Positions are set inside layouts and we need to grab all of them as we do not yet know which layout will be
        // displayed. We also need to register all the positions for the admin.
        $positions = $gantry['configurations']->positions();

        if (!$positions) {
            // No positions are set; display notification in admin.
            add_action( 'load-widgets.php',
                function() {
                    add_action( 'admin_notices', function() {
                        echo '<div class="error"><p>' . __('No widget positions have been defined. Please add some in Gantry 5 Layout Manger or read <a target="_blank" href="http://docs.gantry.org/gantry5/particles/position">documentation</a> on how to create widget positions.', 'gantry5') . '</p></div>';
                    } );
                } );
        } else {
            foreach ( $positions as $name => $title ) {
                // We are just registering positions with defaults; there is an event to override chrome based on the
                // template settings. See \Gantry\Wordpress\Widgets for more information.
                register_sidebar( array(
                    'name'          => __( $title, 'gantry5' ),
                    'id'            => $name,
                    'before_widget' => '<div id="%1s" class="widget %2s">',
                    'after_widget'  => '</div>',
                    'before_title'  => '<h2 class="widgettitle">',
                    'after_title'   => '</h2>',
                ) );
            }
        }
    }

    public function register_post_types()
    {
        //this is where you can register custom post types
    }

    public function register_taxonomies()
    {
        //this is where you can register custom taxonomies
    }

    public function enqueue_scripts()
    {
        Document::registerAssets();
    }

    public function print_styles()
    {
        $styles = Gantry::instance()->styles();
        if ( $styles ) {
            echo implode( "\n    ", $styles ) . "\n";
        }
    }

    public function print_scripts()
    {
        $scripts = Gantry::instance()->scripts();
        if ( $scripts ) {
            echo implode( "\n    ", $scripts ) . "\n";
        }
    }

    public function print_inline_scripts()
    {
        Document::registerScripts('footer');
        $scripts = Gantry::instance()->scripts('footer');
        if ( $scripts ) {
            echo implode( "\n    ", $scripts ) . "\n";
        }
    }

    public function add_to_context( array $context )
    {
        $context = parent::add_to_context( $context );

        $this->url = $context['site']->theme->link;

        if (!$this->user) {
            $this->user = new \TimberUser;
        }

        $context['current_user'] = $this->user;

        return $context;
    }

    public function wordpress($enable = null)
    {
        if ($enable) {
            $this->wordpress = (bool) $enable;
        }

        return $this->wordpress;
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

    protected function updateCookie($name, $value, $expire = 0)
    {
        $path   = SITECOOKIEPATH;
        $domain = COOKIE_DOMAIN;

        setcookie($name, $value, $expire, $path, $domain);
    }

    protected function renderContent($item)
    {
        $context = ['segment' => $item];

        return trim($this->render("@nucleus/content/{$item->type}.html.twig", $context));
    }
}
