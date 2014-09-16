<?php
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_templates')) {
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

if (!defined('GANTRYADMIN_PATH')) {
    define('GANTRYADMIN_PATH', __DIR__);
}

// Bootstrap Gantry framework or fail gracefully (inside included file).
$gantry = include_once __DIR__ . '/includes/gantry.php';
$gantry['theme.path'] = JPATH_SITE . '/templates/gantry';

// Define Gantry Admin services.
$gantry['admin.config'] = function ($c) {
    return \Gantry\Framework\Config::instance(
        JPATH_CACHE . '/gantry/config.php',
        GANTRYADMIN_PATH
    );
};
$gantry['admin.theme'] = function ($c) {
    return new \Gantry\Framework\AdminTheme(GANTRYADMIN_PATH);
};

// Boot the service.
$theme = $gantry['admin.theme'];
$gantry['base_url'] = JUri::base(false) . 'index.php?option=com_gantryadmin';
$gantry['routes'] = [
    'overview' => '',
    'settings' => '&view=settings',
    'page_setup' => '&view=page_setup',
    'page_setup_edit' => '&view=page_setup_edit',
    'page_setup_new' => '&view=page_setup_new',
    'assignments' => '&view=assignments',
    'updates' => '&view=updates',
];

$input = JFactory::getApplication()->input;
$view = $input->getCmd('view', 'overview');
// Render the page.
try {
    echo $theme->render("gantry/{$view}.html.twig");

} catch (Exception $e) {
    if (class_exists( '\Tracy\Debugger' ) && \Tracy\Debugger::isEnabled() && !\Tracy\Debugger::$productionMode ) {
        // We have Tracy enabled; will display and/or log error with it.
        throw $e;
    }

    JError::raiseError(500, $e->getMessage());
}

