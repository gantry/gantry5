<?php

/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die;

use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Timber\Timber;
use Timber\User;

/*
 * The template for displaying Author Archive pages
 */

global $wp_query;

$gantry = Gantry::instance();

/** @var Theme $theme */
$theme  = $gantry['theme'];

// We need to render contents of <head> before plugin content gets added.
$context              = Timber::get_context();
$context['page_head'] = $theme->render('partials/page_head.html.twig', $context);

$context['posts'] = Timber::get_posts();

if (isset($authordata)) {
    $author            = new User($authordata->ID);
    $context['author'] = $author;
    $context['title']  = __('Author:', $context['textdomain']) . ' ' . $author->name();
}

Timber::render(['author.html.twig', 'archive.html.twig', 'index.html.twig'], $context);
