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

Timber::render( array( 'partials/sidebar.html.twig' ), isset($data) ? $data : [] );
