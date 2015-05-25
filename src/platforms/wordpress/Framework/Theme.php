<?php
namespace Gantry\Framework;

use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends Base\Theme
{
    public $path;

    public function __construct( $path, $name = '' )
    {
        parent::__construct($path, $name);

        add_theme_support( 'post-formats' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'menus' );
        add_filter( 'timber_context', array( $this, 'add_to_context' ) );
        add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'widgets_init', array( $this, 'widgets_init' ) );
    }

    public function render($file, array $context = array())
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader = new \Twig_Loader_Filesystem($locator->findResources('gantry-engine://twig'));

        $params = array(
            'cache' => $locator('gantry-cache://twig', true, true),
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => 'html'
        );

        // FIXME: Get timezone from WP.
        $timezone = 'UTC';

        $twig = new \Twig_Environment($loader, $params);
        $twig->getExtension('core')->setTimezone(new \DateTimeZone($timezone));

        $this->add_to_twig($twig);

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        return $twig->render($file, $context);
    }

    public function widgets_init()
    {
        $gantry = Gantry::instance();
        $positions = (array) $gantry['config']->get( 'positions' );

        foreach ( $positions as $name => $params ) {
            $params = (array) $params;
            if ( !isset( $params['name'] ) ) {
                $params['name'] = ucfirst($name);
            }
            register_sidebar( array(
                'name'          => __( $params['name'], 'gantry5' ),
                'id'            => $name,
                'description'   => __( $params['name'], 'gantry5' ),
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
        $context = parent::add_to_context( $context );

        $this->url = $context['site']->theme->link;

        $context['menu'] = new \TimberMenu;
        $context['my'] = new \TimberUser;

        return $context;
    }
}
