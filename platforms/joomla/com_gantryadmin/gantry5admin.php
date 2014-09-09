<?php
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_templates')) {
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';
$gantry['theme.path'] = __DIR__;

include_once __DIR__ . '/includes/theme.php';

// Define Gantry services.
$gantry['theme'] = function ($c) {
    return new Gantry\Admin\Theme($c['theme.path']);
};

// Boot the service.
$theme = $gantry['theme'];

// Render the page.
echo $theme->render('gantry/overview.html.twig');

/*
$controller	= JControllerLegacy::getInstance('GantryAdmin');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
*/

