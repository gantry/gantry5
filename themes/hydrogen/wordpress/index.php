<?php
/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) or die;

/*
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 */

$chooser = new \Gantry\Framework\OutlineChooser;

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry[ 'theme' ];
$theme->setLayout( $chooser->select() );

if ( !class_exists( 'Timber' ) ) {
	_e('Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>', 'g5_hydrogen');
	return;
}

/** Include or exclude categories in the query */
$query = '';

$cat_include = $gantry[ 'config' ]->get( 'content.blog.query.categories.include' );
$cat_exclude = $gantry[ 'config' ]->get( 'content.blog.query.categories.exclude' );

if( $cat_include != '' ) {
    $categories = str_replace( ' ', ',', $cat_include );
    $query = 'cat=' . $categories;
} elseif( $cat_exclude != '' ) {
    $exclude = explode( ' ', $cat_exclude );
    $categories = [];
    foreach( $exclude as $category ) {
        $categories[] = '-' . $category;
    }
    $query = 'cat=' . implode( ',', $categories );
}

$context = Timber::get_context();
$context[ 'posts' ] = Timber::get_posts( $query );

$templates = [ 'index.html.twig' ];

if ( is_home() ) {
	array_unshift( $templates, 'home.html.twig' );
}

Timber::render( $templates, $context );
