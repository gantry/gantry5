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

$raw = JFactory::getApplication()->input->getString('type') == 'raw';

// Reset used outline configuration.
unset($gantry['configuration']);

// Render the component.
echo $theme
    ->setLayout('_body_only', true)
    ->render($raw ? 'raw.html.twig' : 'component.html.twig');
