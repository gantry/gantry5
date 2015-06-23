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
    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('MOD_GANTRY5_PARTICLE_NOT_INITIALIZED', JText::_('MOD_GANTRY5_PARTICLE')),
        'warning'
    );
    return;
}

$gantry = \Gantry\Framework\Gantry::instance();

/** @var Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

/** @var object $params */
$data = json_decode($params->get('particle'), true);

$context = array(
    'gantry' => $gantry,
    'inContent' => true,
    'segment' => array(
        'type' => $data['type'],
        'subtype' => $data['particle'],
        'attributes' =>  $data['options']['particle'],
    )
);

echo $theme->render("@nucleus/content/particle.html.twig", $context);
