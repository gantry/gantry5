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

/** @var object $params */

$gantry = \Gantry\Framework\Gantry::instance();

GANTRY_DEBUGGER && \Gantry\Debugger::startTimer("module-{$module->id}", "Rendering Particle Module #{$module->id}");

// Set up caching.
$cacheid = md5($module->id);

$cacheparams = (object) [
    'cachemode'    => 'id',
    'class'        => 'ModGantryParticlesHelper',
    'method'       => 'render',
    'methodparams' => [$module, $params],
    'modeparams'   => $cacheid
];

$data = JModuleHelper::moduleCache($module, $params, $cacheparams);

if (!is_array($data)) {
    $data = ModGantryParticlesHelper::render($module, $params);
}

list ($html, $assets) = $data;

/** @var \Gantry\Framework\Document $document */
$document = $gantry['document'];
$document->appendHeaderTags($assets);

echo $html;

GANTRY_DEBUGGER && \Gantry\Debugger::stopTimer("module-{$module->id}");


class ModGantryParticlesHelper
{
    /**
     * @param object $module
     * @param object $params
     * @return array
     */
    public static function render($module, $params)
    {
        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Particle Module #{$module->id} was not cached");

        $data = json_decode($params->get('particle'), true);
        $type = $data['type'];
        $particle = $data['particle'];

        $gantry = \Gantry\Framework\Gantry::instance();
        if ($gantry->debug()) {
            $enabled_outline = $gantry['config']->get("particles.{$particle}.enabled", true);
            $enabled = isset($data['options']['particle']['enabled']) ? $data['options']['particle']['enabled'] : true;
            $location = (!$enabled_outline ? 'Outline' : (!$enabled ? 'Module' : null));

            if ($location) {
                return ['<div class="alert alert-error">The Particle has been disabled from the ' . $location . ' and won\'t render.</div>', []];
            }
        }

        $context = array(
            'gantry' => $gantry,
            'inContent' => true,
            'segment' => array(
                'id' => "module-{$particle}-{$module->id}",
                'type' => $type,
                'subtype' => $particle,
                'attributes' => $data['options']['particle'],
            )
        );

        /** @var \Gantry\Framework\Document $document */
        $document = $gantry['document'];
        $document->push();

        /** @var Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];
        $html = trim($theme->render("@nucleus/content/particle.html.twig", $context));

        $assets = $document->pop();

        return [$html, $assets];
    }
}