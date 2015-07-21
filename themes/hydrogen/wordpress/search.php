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
 * Search results page
 */

$chooser = new \Gantry\Framework\OutlineChooser;

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry[ 'theme' ];
$theme->setLayout( $chooser->select() );

$templates = array( 'search.html.twig', 'archive.html.twig', 'index.html.twig' );
$context = Timber::get_context();

$context[ 'title' ] = 'Search results for '. get_search_query();
$context[ 'posts' ] = Timber::get_posts();

Timber::render( $templates, $context );
