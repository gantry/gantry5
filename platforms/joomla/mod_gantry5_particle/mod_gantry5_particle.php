<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
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
$type = $data['type'];
$particle = $data['particle'];

if ($gantry->debug()) {
    $enabled_outline = $gantry['config']->get("particles.{$particle}.enabled", true);
    $enabled = isset($data['options']['particle']['enabled']) ? $data['options']['particle']['enabled'] : true;
    $location = (!$enabled_outline ? 'Outline' : (!$enabled ? 'Module' : null));

    if ($location) {
        echo '<div class="alert alert-error">The Particle has been disabled from the ' . $location . ' and won\'t render.</div>';
        return;
    }
}

$context = array(
    'gantry' => $gantry,
    'inContent' => true,
    'segment' => array(
        'id' => "module-{$particle}-{$module->id}",
        'type' => $type,
        'subtype' => $particle,
        'attributes' =>  $data['options']['particle'],
    )
);

echo trim($theme->render("@nucleus/content/particle.html.twig", $context));
