<?php
namespace Gantry\Theme;

use Gantry\Base\Gantry;
use Symfony\Component\Yaml\Yaml;
use Gantry\Filesystem\File;

class Theme
{
    public $path;

    public function __construct( $path )
    {
        if (!is_dir( $path )) {
            throw new \LogicException( 'Theme not found!' );
        }

        $this->path = $path;

        add_theme_support( 'post-formats' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'menus' );
        add_filter( 'timber_context', array( $this, 'add_to_context' ) );
        add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'widgets_init', array( $this, 'widgets_init' ) );
    }

    public function widgets_init()
    {
        $gantry = \Gantry\Gantry::instance();
        $positions = (array) $gantry->config()->get( 'positions' );

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

    public function add_to_context( $context )
    {
        $gantry = \Gantry\Gantry::instance();
        $context['menu'] = new \TimberMenu;
        $context['my'] = new \TimberUser;
        $context['site'] = $gantry->site();

        // Include Gantry specific things to the context.
        $file = File\Json::instance( $this->path . '/test/nucleus.json' );

        $context['config'] = $gantry->config();
        $context['pageSegments'] = $file->content();
        $context['theme_url'] = $context['site']->theme->link;

        return $context;
    }

    public function add_to_twig( $twig )
    {
        /* this is where you can add your own fuctions to twig */
        $loader = $twig->getLoader();
        $loader->addPath( $this->path . '/nucleus', 'nucleus' );
        $twig->addExtension( new \Twig_Extension_StringLoader() );
        $twig->addFilter( 'toGrid', new \Twig_Filter_Function( array ( $this, 'toGrid' ) ) );
        return $twig;
    }

    public function toGrid( $text )
    {
        static $sizes = array(
            '10'      => 'size-1-10',
            '20'      => 'size-1-5',
            '25'      => 'size-1-4',
            '33.3334' => 'size-1-3',
            '50'      => 'size-1-2',
            '100'     => ''
        );

        return isset( $sizes[$text] ) ? ' ' . $sizes[$text] : '';
    }
}
