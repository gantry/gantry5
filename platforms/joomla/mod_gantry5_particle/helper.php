<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

use Gantry\Component\Content\Block\ContentBlockInterface;
use Gantry\Component\Content\Block\HtmlBlock;
use Gantry\Debugger;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Theme;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * Class ModGantry5ParticleHelper
 */
class ModGantry5ParticleHelper
{
    /**
     * Serve module AJAX requests in 'index.php?option=com_ajax&module=gantry5_particle&format=json'.
     *
     * @return array|null|string
     */
    public static function getAjax()
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        $input = $app->input;
        $format = strtolower($input->getCmd('format', 'html'));
        $id = $input->getInt('id');

        $props = $_GET;
        unset($props['option'], $props['module'], $props['format'], $props['id']);

        return static::ajax($id, $props, $format);
    }

    /**
     * @param $id
     * @param array $props
     * @param string $format
     * @return array|null|string
     */
    public static function ajax($id, $props = [], $format = 'raw')
    {
        if (!in_array($format, ['json', 'raw', 'debug'])) {
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $gantry = Gantry::instance();

        /** @var Platform $platform */
        $platform = $gantry['platform'];
        $module = $platform->getModule($id);

        // Make sure that module really exists.
        if (!is_object($module) || strpos($module->module, 'gantry5') === false) {
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $attribs = ['style' => 'gantry'];

        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Trigger the onRenderModule event.
        $app->triggerEvent('onRenderModule', ['module' => &$module, 'attribs' => &$attribs]);

        $params = new Registry($module->params);
        $params->set('ajax', $props);
        $block = static::render($module, $params);
        $data = json_decode($params->get('particle'), true);
        $type = $data['type'] . '.' . $data['particle'];
        $identifier = static::getIdentifier($data['particle'], $module->id);
        $html = (string) $block;

        if ($format === 'raw') {
            return $html;
        }

        return ['code' => 200, 'type' => $type, 'id' => $identifier, 'props' => (object) $props, 'html' => $html];
    }

    /**
     * @param object $module
     * @param object $params
     * @return ContentBlockInterface
     */
    public static function render($module, $params)
    {
        if (\GANTRY_DEBUGGER) {
            Debugger::addMessage("Particle Module #{$module->id} was not cached");
        }

        $data = json_decode($params->get('particle'), true);
        $type = $data['type'];
        $particle = $data['particle'];

        $gantry = Gantry::instance();
        if ($gantry->debug()) {
            $enabled_outline = $gantry['config']->get("particles.{$particle}.enabled", true);
            $enabled = isset($data['options']['particle']['enabled']) ? $data['options']['particle']['enabled'] : true;
            $location = (!$enabled_outline ? 'Outline' : (!$enabled ? 'Module' : null));

            if ($location) {
                $block = HtmlBlock::create();
                $block->setContent(sprintf('<div class="alert alert-error">The Particle has been disabled from the %s and won\'t render.</div>', $location));

                return $block;
            }
        }

        $id = static::getIdentifier($particle, $module->id);
        $object = (object) array(
            'id' => $id,
            'type' => $type,
            'subtype' => $particle,
            'attributes' => $data['options']['particle'],
        );

        $context = array(
            'gantry' => $gantry,
            'inContent' => true,
            'ajax' => $params->get('ajax'),
        );

        /** @var Theme $theme */
        $theme = $gantry['theme'];
        $block = $theme->getContent($object, $context);

        // Create outer block with the particle ID for AJAX calls.
        $outer = \Gantry\Component\Content\Block\HtmlBlock::create();
        $outer->setContent('<div id="' . $id . '-particle" class="g-particle">' . $block->getToken() . '</div>');
        $outer->addBlock($block);

        return $outer;
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
     * @return ContentBlockInterface|null
     */
    public static function moduleCache($module, $params, $cacheparams)
    {
        $block = (array) ModuleHelper::moduleCache($module, $params, $cacheparams);
        try {
            return $block ? HtmlBlock::fromArray($block) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $particle
     * @param string $id
     * @return string
     */
    public static function getIdentifier($particle, $id)
    {
        return "module-{$particle}-{$id}";
    }
}
