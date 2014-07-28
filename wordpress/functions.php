<?php

use \Symfony\Component\Yaml\Yaml;

if ( !class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . admin_url( 'plugins.php#timber' ) . '">' . admin_url( 'plugins.php' ) . '</a></p></div>';
	} );
	return;
}

class GantrySite extends TimberSite {

	function __construct() {
		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
		parent::__construct();
	}

	function widgets_init() {
		$positions = Yaml::parse(__DIR__ . '/test/positions.yaml');

		foreach ($positions['positions'] as $name => $params) {
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

	/**
	 * @param int $widget_id
	 * @return TimberFunctionWrapper
	 */
	public function sidebar($widget_id = '') {
		return TimberHelper::function_wrapper('dynamic_sidebar', array($widget_id), true);
	}

	function register_post_types() {
		//this is where you can register custom post types
	}

	function register_taxonomies() {
		//this is where you can register custom taxonomies
	}

	function add_to_context( $context ) {
		$context['menu'] = new TimberMenu();
		$context['site'] = $this;

		// Include Gantry specific things to the context.
		$context['pageSegments'] = (array) json_decode( file_get_contents( __DIR__ . '/test/nucleus.json' ), true );
        $context['theme'] = (array) Yaml::parse(file_get_contents(__DIR__ . '/nucleus.yaml'));

        return $context;
	}

	function add_to_twig( $twig ) {
		/* this is where you can add your own fuctions to twig */
		$loader = $twig->getLoader();
		$loader->addPath( __DIR__ . '/nucleus', 'nucleus' );
		$twig->addExtension( new Twig_Extension_StringLoader() );
		$twig->addFilter('toGrid', new Twig_Filter_Function( 'toGrid' ) );
		return $twig;
	}

}

new GantrySite();

function toGrid( $text ) {
	static $sizes = array(
		'10'      => 'size-1-10',
		'20'      => 'size-1-5',
		'25'      => 'size-1-4',
		'33.3334' => 'size-1-3',
		'50'      => 'size-1-2',
		'100'     => ''
	);

    return isset($sizes[$text]) ? ' ' . $sizes[$text] : '';
}
