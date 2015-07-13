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
 * The template for displaying Author Archive pages
 */

$chooser = new \Gantry\Framework\OutlineChooser;

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry[ 'theme' ];
$theme->setLayout( $chooser->select() );

global $wp_query;

$context = Timber::get_context();
$context[ 'posts' ] = Timber::get_posts();

if( isset( $query_vars[ 'author' ] ) ) {
	$author = new TimberUser( $wp_query->query_vars[ 'author' ] );
	$context[ 'author' ] = $author;
	$context[ 'title' ] = 'Author Archives: ' . $author->name();
}

Timber::render( array( 'author.html.twig', 'archive.html.twig' ), $context );
