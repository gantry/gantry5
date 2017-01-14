<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
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
     * @return Gantry\Component\Content\Block\ContentBlockInterface
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
                $block = \Gantry\Component\Content\Block\HtmlBlock::create();
                $block->setContent(sprintf('<div class="alert alert-error">The Particle has been disabled from the %s and won\'t render.</div>', $location));

                return $block;
            }
        }

        $object = (object) array(
            'id' => "module-{$particle}-{$module->id}",
            'type' => $type,
            'subtype' => $particle,
            'attributes' => $data['options']['particle'],
        );

        $context = array(
            'gantry' => $gantry,
            'inContent' => true
        );

        /** @var Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];
        $block = $theme->getContent($object, $context);

        return $block;
    }

    /**
     * @param $module
     * @param $params
     * @return array
     */
    public static function cache($module, $params)
    {
        return static::render($module, $params)->toArray();
    }

    /**
     * @param $module
     * @param $params
     * @param $cacheparams
     * @return \Gantry\Component\Content\Block\ContentBlockInterface|null
     */
    public static function moduleCache($module, $params, $cacheparams)
    {
        $block = (array) JModuleHelper::moduleCache($module, $params, $cacheparams);
        try {
            return $block ? \Gantry\Component\Content\Block\HtmlBlock::fromArray($block) : null;
        } catch (Exception $e) {
            return null;
        }
    }
}