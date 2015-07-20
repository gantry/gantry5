<?php
namespace Gantry\Framework;

use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends Base\Theme
{
    public $path;
    protected $user;

    public function __construct( $path, $name = '' )
    {
        parent::__construct($path, $name);

        add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'widgets' ) );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-formats' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'menus' );
        add_filter( 'timber_context', array( $this, 'add_to_context' ) );
        add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'widgets_init', array( $this, 'widgets_init' ) );
    }

    public function debug()
    {
        return WP_DEBUG;
    }

    public function renderer()
    {
        if (!$this->renderer) {
            $gantry = \Gantry\Framework\Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $loader = new \Twig_Loader_Filesystem($locator->findResources('gantry-engine://twig'));

            $params = array(
                'cache' => $locator->findResource('gantry-cache://theme/twig', true, true),
                'debug' => true,
                'auto_reload' => true,
                'autoescape' => 'html'
            );

            $twig = new \Twig_Environment($loader, $params);

            // FIXME: Get timezone from WP.
            //$timezone = 'UTC';
            //$twig->getExtension('core')->setTimezone(new \DateTimeZone($timezone));

            $this->add_to_twig($twig);

            $this->renderer = $twig;
        }

        return $this->renderer;
    }


    public function render($file, array $context = array())
    {
        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        return $this->renderer()->render($file, $context);
    }

    public function widgets_init()
    {
        $gantry = Gantry::instance();

        $positions = $gantry['configurations']->positions();

        foreach ( $positions as $name => $title ) {
            // FIXME
            // This should be handled by theme so translation plugins could catch it as part of theme.
            // This stuff might also need take Joomla chromes into account for cross-compatibility reasons
            register_sidebar( array(
                'name'          => __( $title, 'gantry5' ),
                'id'            => $name,
                'description'   => __( $title, 'gantry5' ),
                'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                'after_widget'  => '</aside>',
                'before_title'  => '<h3 class="widget-title">',
                'after_title'   => '</h3>',
            ) );
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

    public function add_to_context( array $context )
    {
        if (!$this->user) {
            $this->user = new \TimberUser;
        }

        $context = parent::add_to_context( $context );

        $this->url = $context['site']->theme->link;
        $context['my'] = $this->user;

        return $context;
    }
}
