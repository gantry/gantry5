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
    $lang->load('com_gantry5', JPATH_ADMINISTRATOR) || $lang->load('com_gantry5', JPATH_ADMINISTRATOR . '/components/com_gantry5');

    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('COM_GANTRY5_PARTICLE_NOT_INITIALIZED', JText::_('COM_GANTRY5_COMPONENT')),
        'warning'
    );
    return;
}

$app = JFactory::getApplication();
$document = JFactory::getDocument();
$input = $app->input;
$menu = $app->getMenu();
$menuItem = $menu->getActive();

// Prevent direct access without menu item.
if (!$menuItem) {
    JError::raiseError(404, JText::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'));
}

// Handle non-html formats and error page.
if ($input->getCmd('format', 'html') !== 'html' || $input->getCmd('view') === 'error' || $input->getInt('g5_not_found')) {
    JError::raiseError(404, JText::_('JERROR_PAGE_NOT_FOUND'));
}

$gantry = \Gantry\Framework\Gantry::instance();

/** @var Gantry\Framework\Theme $theme */
$theme = $gantry['theme'];

$params = $app->getParams();

// Set page title.
$title = $params->get('page_title');
if (empty($title)) {
    $title = $app->get('sitename');
} elseif ($app->get('sitename_pagetitles', 0) == 1) {
    $title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
} elseif ($app->get('sitename_pagetitles', 0) == 2) {
    $title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
}
$document->setTitle($title);

// Set description.
if ($params->get('menu-meta_description')) {
    $document->setDescription($params->get('menu-meta_description'));
}

// Set Keywords.
if ($params->get('menu-meta_keywords')) {
    $document->setMetadata('keywords', $params->get('menu-meta_keywords'));
}

// Set robots.
if ($params->get('robots')) {
    $document->setMetadata('robots', $params->get('robots'));
}

/** @var object $params */
$data = json_decode($params->get('particle'), true);
if (!$data) {
    // No component output.
    return;
}

$context = [
    'gantry' => $gantry,
    'noConfig' => true,
    'inContent' => true,
    'segment' => [
        'id' => 'main-particle',
        'type' => $data['type'],
        'classes' => $params->get('pageclass_sfx'),
        'subtype' => $data['particle'],
        'attributes' => $data['options']['particle'],
    ]
];

// Render the particle.
echo trim($theme->render("@nucleus/content/particle.html.twig", $context));
