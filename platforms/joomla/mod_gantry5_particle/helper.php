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