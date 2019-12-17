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

// Detect Gantry Framework or fail gracefully.
if (!class_exists('Gantry\Framework\Gantry')) {
    $lang = JFactory::getLanguage();
    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('MOD_GANTRY5_PARTICLE_NOT_INITIALIZED', JText::_('MOD_GANTRY5_PARTICLE')),
        'warning'
    );
    return;
}

include_once dirname(__FILE__) . '/helper.php';

/** @var object $params */

$gantry = \Gantry\Framework\Gantry::instance();

GANTRY_DEBUGGER && \Gantry\Debugger::startTimer("module-{$module->id}", "Rendering Particle Module #{$module->id}");

// Set up caching.
$cacheid = md5($module->id);

$cacheparams = (object) [
    'cachemode'    => 'id',
    'class'        => 'ModGantry5ParticleHelper',
    'method'       => 'cache',
    'methodparams' => [$module, $params],
    'modeparams'   => $cacheid
];

$block = ModGantry5ParticleHelper::moduleCache($module, $params, $cacheparams);
if (!$block) {
    $block = ModGantry5ParticleHelper::render($module, $params);
}

/** @var \Gantry\Framework\Document $document */
$document = $gantry['document'];
$document->addBlock($block);

echo $block->toString();

GANTRY_DEBUGGER && \Gantry\Debugger::stopTimer("module-{$module->id}");
