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

global $wp_query;

$data = Timber::get_context();
$data['posts'] = Timber::get_posts();

if ( isset( $query_vars['author'] ) ) {
	$author = new TimberUser( $wp_query->query_vars['author'] );
	$data['author'] = $author;
	$data['title'] = 'Author Archives: ' . $author->name();
}

Timber::render( array( 'author.twig', 'archive.twig' ), $data );
