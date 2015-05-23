<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

// Detect Gantry Framework or fail gracefully.
if (!class_exists('Gantry\Framework\Gantry')) {
    $lang = JFactory::getLanguage();
    $this->app->enqueueMessage(
        JText::sprintf('MOD_GANTRY5_PARTICLE_NOT_INITIALIZED', JText::_('MOD_GANTRY5_PARTICLE')),
        'warning'
    );
    return;
}

// Include the class only once
require_once __DIR__ . '/class.php';

$gantry = \Gantry\Framework\Gantry::instance();

/** @var Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$params = [
    'particle' => $gantry['config']->get('particles.copyright')
];

/** @var object $params */
return $theme->render('@particles/copyright.html.twig', $params);
