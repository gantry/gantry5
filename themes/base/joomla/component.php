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
use Joomla\CMS\Factory;

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

$raw = Factory::getApplication()->input->getString('type') === 'raw';

// Reset used outline configuration.
unset($gantry['configuration']);

// Render the component.
echo $theme
    ->setLayout('_body_only', true)
    ->render($raw ? 'raw.html.twig' : 'component.html.twig');
