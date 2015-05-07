<?php
defined('_JEXEC') or die;

$app = JFactory::getApplication();
$input = $app->input;

// Prevent direct access without menu item.
if (!$input->getInt('Itemid')) {
    throw new RuntimeException(JText::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
}

// Handle error page.
if ($input->getCmd('view') === 'error') {
    throw new RuntimeException('Page not found', 404);
}
