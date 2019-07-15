<?php
/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die;

use Timber\Timber;

/*
 * The template for displaying Author Archive pages
 */

global $wp_query;

$gantry = Gantry\Framework\Gantry::instance();
$theme  = $gantry['theme'];

// We need to render contents of <head> before plugin content gets added.
$context              = Timber::get_context();
$context['page_head'] = $theme->render('partials/page_head.html.twig', $context);

$context['posts'] = Timber::get_posts();

if (isset($authordata)) {
    $author            = new \Timber\User($authordata->ID);
    $context['author'] = $author;
    $context['title']  = __('Author:', 'g5_helium') . ' ' . $author->name();
}

Timber::render(['author.html.twig', 'archive.html.twig', 'index.html.twig'], $context);
