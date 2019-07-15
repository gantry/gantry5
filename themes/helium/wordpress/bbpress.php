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
 * The template for displaying BBPress pages
 */

$gantry = Gantry\Framework\Gantry::instance();
$theme  = $gantry['theme'];

// We need to render contents of <head> before plugin content gets added.
$context              = Timber::get_context();
$context['page_head'] = $theme->render('partials/page_head.html.twig', $context);

$context['posts']   = Timber::query_post();
$context['content'] = \Timber\Helper::ob_function('the_content');

Timber::render('bbpress.html.twig', $context);
