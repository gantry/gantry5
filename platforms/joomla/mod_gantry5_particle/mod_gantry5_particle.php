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

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Debugger;

// Detect Gantry Framework or fail gracefully.
if (!class_exists('Gantry\Framework\Gantry')) {
    $lang = JFactory::getLanguage();
    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('MOD_GANTRY5_PARTICLE_NOT_INITIALIZED', JText::_('MOD_GANTRY5_PARTICLE')),
        'warning'
    );
    return;
}

include_once __DIR__ . '/helper.php';

/** @var object $params */
/** @var object $module */

$gantry = Gantry::instance();

GANTRY_DEBUGGER && Debugger::startTimer("module-{$module->id}", "Rendering Particle Module #{$module->id}");

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
if (null === $block) {
    $block = ModGantry5ParticleHelper::render($module, $params);
}

/** @var Document $document */
$document = $gantry['document'];
$document->addBlock($block);

echo $block->toString();

GANTRY_DEBUGGER && Debugger::stopTimer("module-{$module->id}");
