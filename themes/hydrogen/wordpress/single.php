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
 * The Template for displaying all single posts
 */

$chooser = new \Gantry\Framework\OutlineChooser;

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry[ 'theme' ];
$theme->setLayout( $chooser->select() );

$context = Timber::get_context();
$post = Timber::query_post();

$context[ 'post' ] = $post;
$context[ 'wp_title' ] .= ' - ' . $post->title();
$context[ 'comment_form' ] = TimberHelper::get_comment_form();

if ( post_password_required( $post->ID ) ) {
	Timber::render( 'single-password.html.twig', $context );
} else {
	Timber::render( array( 'single-' . $post->ID . '.html.twig', 'single-' . $post->post_type . '.html.twig', 'single.html.twig' ), $context );
}
