<?php

/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

use Gantry\Framework\Theme;

defined('ABSPATH') or die;

/*
 * WARNING: This file will be overridden during theme update. Do not change this file!
 *
 * If you want to add your custom functions, put your code into `custom/functions.php` instead!
 */

// Note: This file must be PHP 5.6 compatible.

// Check min. required version of Gantry 5
$requiredGantryVersion = '5.5';
$translationDomain = 'g5_helium';

// Bootstrap Gantry framework or fail gracefully.
$gantry_include = locate_template('/custom/includes/gantry.php') ?: locate_template('/includes/gantry.php');
if (!$gantry_include) {
    wp_die('Gantry theme is missing a file: includes/gantry.php');
}

$gantry = require $gantry_include;
if (!$gantry) {
    return;
}

if (!$gantry->isCompatible($requiredGantryVersion)) {
    $current_theme = wp_get_theme();
    $error = sprintf(__('Please upgrade Gantry 5 Framework to v%s (or later) before using %s theme!', $translationDomain), strtoupper($requiredGantryVersion), $current_theme->get('Name'));

    if(is_admin()) {
        add_action('admin_notices', static function () use ($error) {
            echo '<div class="error"><p>' . $error . '</p></div>';
        });
    } else {
        wp_die($error);
    }
}

/** @var Theme $theme */
$theme = $gantry['theme'];

// Theme helper files that can contain useful methods or filters
$helpers = array(
    'includes/helper.php', // General helper file
);

// Require custom Functions if the file exists (allows overriding helpers).
if ($customInclude = locate_template('custom/functions.php')) {
    require $customInclude;
}

foreach ($helpers as $file) {
    if (!$filepath = locate_template($file)) {
        trigger_error(sprintf(__('Error locating %s for inclusion', $translationDomain), $file), E_USER_ERROR);
    }

    require $filepath;
}
