<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Module\Gantry5Particle\Site\Helper;

use Gantry\Component\Content\Block\HtmlBlock;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Theme;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Event\Module;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_gantry_particle
 */
class Gantry5ParticleHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Retrieve a particle
     *
     * @param   Registry         $params  The module parameters.
     * @param   SiteApplication  $app     The current application.
     *
     * @return  string|false
     */
    public function getParticle($module, Registry $params, SiteApplication $app): false|string
    {
        if (!class_exists('Gantry\Framework\Gantry')) {
            $app->enqueueMessage(
                Text::sprintf('MOD_GANTRY5_PARTICLE_NOT_INITIALIZED', Text::_('MOD_GANTRY5_PARTICLE')),
                'warning'
            );

            return false;
        }

        $gantry = Gantry::instance();

        /** @var Document $document */
        $document = $gantry['document'];

        $data     = \json_decode($params->get('particle'), true);
        $type     = $data['type'];
        $particle = $data['particle'];

        if ($gantry->debug()) {
            $enabled_outline = $gantry['config']->get("particles.{$particle}.enabled", true);
            $enabled = $data['options']['particle']['enabled'] ?? true;
            $location = !$enabled_outline ? 'Outline' : (!$enabled ? 'Module' : null);

            if ($location) {
                $block = HtmlBlock::create();
                $block->setContent(
                    sprintf('<div class="alert alert-error">The Particle has been disabled from the %s and won\'t render.</div>', $location)
                );

                $document->addBlock($block);

                return $block->toString();
            }
        }

        $id = static::getIdentifier($particle, $module->id);

        $object = (object) [
            'id'         => $id,
            'type'       => $type,
            'subtype'    => $particle,
            'attributes' => $data['options']['particle'],
        ];

        $context = [
            'gantry'    => $gantry,
            'inContent' => true,
            'ajax'      => $params->get('ajax'),
        ];

        /** @var Theme $theme */
        $theme = $gantry['theme'];
        $block = $theme->getContent($object, $context);

        // Create outer block with the particle ID for AJAX calls.
        $outer = HtmlBlock::create();
        $outer->setContent('<div id="' . $id . '-particle" class="g-particle">' . $block->getToken() . '</div>');
        $outer->addBlock($block);

        $document->addBlock($block);

        return $block->toString();
    }

    /**
     * Serve module AJAX requests in 'index.php?option=com_ajax&module=gantry5_particle&method=get&format=json'.
     *
     * @return array|null|string
     */
    public function getAjax()
    {
        $input = Factory::getApplication()->getInput();

        $format = \strtolower($input->getCmd('format', 'html'));
        $id     = $input->getInt('id');
        $props  = $_GET;

        unset($props['option'], $props['module'], $props['format'], $props['id']);

        return $this->ajax($id, $props, $format);
    }

    /**
     * @param $id
     * @param array $props
     * @param string $format
     * @return array|null|string
     */
    public function ajax($id, $props = [], $format = 'raw')
    {
        if (!in_array($format, ['json', 'raw', 'debug'])) {
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        /** @var SiteApplication $app */
        $app    = Factory::getApplication();
        $gantry = Gantry::instance();

        /** @var Platform $platform */
        $platform = $gantry['platform'];
        $module   = $platform->getModule($id);

        // Make sure that module really exists.
        if (!\is_object($module) || \strpos($module->module, 'gantry5') === false) {
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $attribs = ['style' => 'gantry'];

        $dispatcher = $app->getDispatcher();

        $dispatcher->dispatch('onRenderModule', new Module\BeforeRenderModuleEvent('onRenderModule', [
            'subject'    => $module,
            'attributes' => $attribs,
        ]));

        $params = new Registry($module->params);
        $params->set('ajax', $props);

        $block      = $this->getParticle($module, $params, $app);
        $data       = \json_decode($params->get('particle'), true);
        $type       = $data['type'] . '.' . $data['particle'];
        $identifier = static::getIdentifier($data['particle'], $module->id);
        $html       = (string) $block;

        if ($format === 'raw') {
            return $html;
        }

        return [
            'type' => $type,
            'id' => $identifier,
            'props' => (object) $props,
            'html' => $html
        ];
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
