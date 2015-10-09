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

/*
 * The template to display when page is offline
 */

$chooser = new \Gantry\Framework\OutlineChooser;

$gantry = Gantry\Framework\Gantry::instance();
$theme  = $gantry['theme'];

$theme->setLayout('_offline', true);

// We need to render contents of <head> before plugin content gets added.
$context              = Timber::get_context();
$context['page_head'] = $gantry->isCompatible('5.1.5') ? $theme->render('partials/page_head.html.twig', $context) : null;

Timber::render('offline.html.twig', $context);
