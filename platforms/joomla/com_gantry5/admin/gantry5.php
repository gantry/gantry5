<?php
defined('_JEXEC') or die;

$user = JFactory::getUser();

// ACL for hardening the access to the template manager.
if ((!$user->authorise('core.manage', 'com_templates') || !$user->authorise('core.manage', 'com_gantry5'))
    || !$user->authorise('core.edit', 'com_templates')
    || !$user->authorise('core.create', 'com_templates')
    || !$user->authorise('core.admin', 'com_templates')) {
    $app  = JFactory::getApplication();
    $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');

    return false;
}

if (!defined('GANTRYADMIN_PATH')) {
    define('GANTRYADMIN_PATH', JPATH_COMPONENT_ADMINISTRATOR);
}

// Setup Gantry Framework or throw exception.
Gantry5\Loader::setup();

$gantry = Gantry\Framework\Gantry::instance();
$gantry['router'] = function ($c) {
    return new \Gantry\Admin\Router($c);
};

$gantry['router']->dispatch();
