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

$user = JFactory::getUser();
$app = JFactory::getApplication();

// ACL for Gantry admin access.
if (!$user->authorise('core.manage', 'com_gantry5')
    && !$user->authorise('core.manage', 'com_templates')
    // Editing particle module makes AJAX call to Gantry component, but has restricted access to json only.
    && !($user->authorise('core.manage', 'com_modules') && $app->input->request->getString('format') === 'json')
) {
    $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');

    return false;
}

if (!defined('GANTRYADMIN_PATH')) {
    define('GANTRYADMIN_PATH', JPATH_COMPONENT_ADMINISTRATOR);
}

// Detect Gantry Framework or fail gracefully.
if (!class_exists('Gantry5\Loader')) {
    $app->enqueueMessage(
        JText::sprintf('COM_GANTRY5_PLUGIN_MISSING', JText::_('COM_GANTRY5')),
        'error'
    );
    return;
}

// Initialize administrator or fail gracefully.
try {
    Gantry5\Loader::setup();

    $gantry = Gantry\Framework\Gantry::instance();
    $gantry['router'] = function ($c) {
        return new \Gantry\Admin\Router($c);
    };

} catch (Exception $e) {
    $app->enqueueMessage(
        JText::sprintf($e->getMessage()),
        'error'
    );
    return;
}

// Dispatch to the controller.
$gantry['router']->dispatch();
