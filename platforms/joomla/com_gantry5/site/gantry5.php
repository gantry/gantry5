<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/** @var CMSApplication $application */
$application = Factory::getApplication();

// Detect Gantry Framework or fail gracefully.
if (!class_exists('Gantry\Framework\Gantry')) {
    $language = $application->getLanguage();
    $language->load('com_gantry5', JPATH_ADMINISTRATOR)
    || $language->load('com_gantry5', JPATH_ADMINISTRATOR . '/components/com_gantry5');

    $application->enqueueMessage(
        Text::sprintf('COM_GANTRY5_PARTICLE_NOT_INITIALIZED', Text::_('COM_GANTRY5_COMPONENT')),
        'warning'
    );

    return;
}

$document = $application->getDocument();
$input = $application->input;
$menu = $application->getMenu();
$menuItem = $menu->getActive();

$gantry = Gantry::instance();

// Prevent direct access without menu item.
if (!$menuItem) {
    if (isset($gantry['errors'])) {
        /** @var \Whoops\Run $errors */
        $errors = $gantry['errors'];
        $errors->unregister();
    }

    throw new Exception(Text::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
}

// Handle non-html formats and error page.
if ($input->getCmd('view') === 'error' || $input->getInt('g5_not_found') || strtolower($input->getCmd('format', 'html')) !== 'html') {
    if (isset($gantry['errors'])) {
        /** @var \Whoops\Run $errors */
        $errors = $gantry['errors'];
        $errors->unregister();
    }

    throw new Exception(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
}

$gantry = Gantry::instance();

/** @var Theme $theme */
$theme = $gantry['theme'];

/** @var Registry $params */
$params = $application->getParams();

// Set page title.
$title = $params->get('page_title');
if (empty($title)) {
    $title = $application->get('sitename');
} elseif ($application->get('sitename_pagetitles', 0) == 1) {
    $title = Text::sprintf('JPAGETITLE', $application->get('sitename'), $title);
} elseif ($application->get('sitename_pagetitles', 0) == 2) {
    $title = Text::sprintf('JPAGETITLE', $title, $application->get('sitename'));
}
$document->setTitle($title);

// Set description.
if ($params->get('menu-meta_description')) {
    $document->setDescription($params->get('menu-meta_description'));
}

// Set Keywords.
if ($params->get('menu-meta_keywords')) {
    $document->setMetaData('keywords', $params->get('menu-meta_keywords'));
}

// Set robots.
if ($params->get('robots')) {
    $document->setMetaData('robots', $params->get('robots'));
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
echo trim($theme->render('@nucleus/content/particle.html.twig', $context));
