<?php
/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 */

$chooser = new \Gantry\Framework\OutlineChooser;

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry[ 'theme' ];
$theme->setLayout( $chooser->select() );

$templates = array( 'archive.html.twig', 'index.html.twig' );

$context = Timber::get_context();

$context[ 'title' ] = 'Archive';
if( is_day() ) {
    $context[ 'title' ] = 'Archive: ' . get_the_date( 'D M Y' );
} else if( is_month() ) {
    $context[ 'title' ] = 'Archive: ' . get_the_date( 'M Y' );
} else if( is_year() ) {
    $context[ 'title' ] = 'Archive: ' . get_the_date( 'Y' );
} else if( is_tag() ) {
    $context[ 'title' ] = single_tag_title( '', false );
} else if( is_category() ) {
    $context[ 'title' ] = single_cat_title( '', false );
    array_unshift( $templates, 'archive-' . get_query_var( 'cat' ) . '.html.twig' );
} else if( is_post_type_archive() ) {
    $context[ 'title' ] = post_type_archive_title( '', false );
    array_unshift( $templates, 'archive-' . get_post_type() . '.html.twig' );
}

$context[ 'posts' ] = Timber::get_posts();
$context[ 'pagination' ] = Timber::get_pagination();

Timber::render( $templates, $context );
