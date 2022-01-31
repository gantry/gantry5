<?php

/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Gantry\Framework\Platform;
use Gantry\Framework\Theme;

// Bootstrap Gantry framework or fail gracefully (inside included file).
$className = __DIR__ . '/custom/includes/gantry.php';
if (!is_file($className)) {
    $className = __DIR__ . '/includes/gantry.php';
}
$gantry = include $className;

/** @var Platform $joomla */
$joomla = $gantry['platform'];
$joomla->document = $this;

/** @var Theme $theme */
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
