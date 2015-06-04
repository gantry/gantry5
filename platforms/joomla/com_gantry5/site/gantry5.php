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
    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('COM_GANTRY5_PARTICLE_NOT_INITIALIZED', JText::_('COM_GANTRY5_COMPONENT')),
        'warning'
    );
    return;
}

$app = JFactory::getApplication();
$input = $app->input;
$menu = $app->getMenu();
$menuItem = $menu->getActive();

// Prevent direct access without menu item.
if (!$menuItem) {
    JError::raiseError(404, JText::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'));
}

// Handle error page.
if ($input->getCmd('view') === 'error') {
    JError::raiseError(404, 'Page not found');
}

$gantry = \Gantry\Framework\Gantry::instance();

/** @var Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$params = $menu->getParams($menuItem->id);

/** @var object $params */
$data = json_decode($params->get('particle'), true);
if (!$data) {
    return;
}

$context = [
    'gantry' => $gantry,
    'noConfig' => true,
    'inContent' => true,
    'segment' => [
        'type' => $data['type'],
        'subtype' => $data['particle'],
        'attributes' =>  $data['options']['particle'],
    ]
];

// Render the page.
echo $theme->render("@nucleus/content/particle.html.twig", $context);
