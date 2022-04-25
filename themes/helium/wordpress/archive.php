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

/*
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 */

$gantry = Gantry::instance();

/** @var Theme $theme */
$theme  = $gantry['theme'];

// We need to render contents of <head> before plugin content gets added.
$context              = Timber::get_context();
$context['page_head'] = $theme->render('partials/page_head.html.twig', $context);

$templates = ['archive.html.twig', 'index.html.twig'];

$textdomain = $context['textdomain'];

$context['title'] = __('Archive', $textdomain);
if (is_day()) {
    $context['title'] = __('Archive:', $textdomain) . ' ' . get_the_date('j F Y');
} else if (is_month()) {
    $context['title'] = __('Archive:', $textdomain) . ' ' . get_the_date('F Y');
} else if (is_year()) {
    $context['title'] = __('Archive:', $textdomain) . ' ' . get_the_date('Y');
} else if (is_tag()) {
    $context['title'] = single_tag_title('', false);
} else if (is_category()) {
    $context['title'] = single_cat_title('', false);
    array_unshift($templates, 'archive-' . get_query_var('cat') . '.html.twig');
} else if (is_post_type_archive()) {
    $context['title'] = post_type_archive_title('', false);
    array_unshift($templates, 'archive-' . get_post_type() . '.html.twig');
}

$context['posts'] = Timber::get_posts();

Timber::render($templates, $context);
