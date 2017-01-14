<?php
/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include __DIR__ . '/includes/gantry.php';

/** @var \Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

ob_start();
include JPATH_THEMES . '/system/offline.php';
$html = ob_get_clean();
$start = strpos($html, '<body>') + 6;
$end = strpos($html, '</body>', $start);

$context = array(
    'message' => substr($html, $start, $end - $start)
);

// Reset used outline configuration.
unset($gantry['configuration']);

// Render the page.
echo $theme
    ->setLayout('_offline', true)
    ->render('offline.html.twig', $context);
