<?php
defined('_JEXEC') or die;

$user = JFactory::getUser();
$app = JFactory::getApplication();

// ACL for hardening the access to the template manager.
if ((!$user->authorise('core.manage', 'com_templates') || !$user->authorise('core.manage', 'com_gantry5'))
    || !$user->authorise('core.edit', 'com_templates')
    || !$user->authorise('core.create', 'com_templates')
    || !$user->authorise('core.admin', 'com_templates')) {
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
