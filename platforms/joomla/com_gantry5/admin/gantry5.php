<?php

/**
 * @package   Gantry 5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

use Gantry\Admin\Router;
use Gantry\Component\Admin\ModuleEditorAccess;
use Gantry\Framework\Gantry;
use Gantry5\Loader;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/** @var AdministratorApplication $app */
$app = Factory::getApplication();
$user = $app->getIdentity();

$hasGantryAccess = $user && (
    $user->authorise('core.manage', 'com_gantry5')
    || $user->authorise('core.manage', 'com_templates')
);

$modulesCandidate = $user
    && !$hasGantryAccess
    && $user->authorise('core.manage', 'com_modules');

// ACL for Gantry admin access.
if (!$hasGantryAccess && !$modulesCandidate) {
    $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

    return false;
}

if (!defined('GANTRYADMIN_PATH')) {
    define('GANTRYADMIN_PATH', JPATH_COMPONENT_ADMINISTRATOR);
}

// Detect Gantry Framework or fail gracefully.
if (!class_exists('Gantry5\Loader')) {
    $app->enqueueMessage(
        Text::sprintf('COM_GANTRY5_PLUGIN_MISSING', Text::_('COM_GANTRY5')),
        'error'
    );
    return;
}

// Initialize administrator or fail gracefully.
try {
    Loader::setup();

    if ($modulesCandidate) {
        $method = ModuleEditorAccess::effectiveMethod(
            $app->input->server->getCmd('REQUEST_METHOD', 'GET'),
            $app->input->server->getCmd('HTTP_X_HTTP_METHOD_OVERRIDE', '')
                ?: $app->input->server->getCmd('X-HTTP-METHOD-OVERRIDE', ''),
            $app->input->post->getCmd('METHOD', '')
        );

        $restrictedDenied = !ModuleEditorAccess::isAllowed(
            $app->input->getString('view', ''),
            $app->input->getCmd('format', 'html'),
            $method
        );
    }

    if (!empty($restrictedDenied)) {
        // Thrown after the try/catch so the request gets a real HTTP 403.
        $forbidden = ModuleEditorAccess::forbidden(Text::_('JERROR_ALERTNOAUTHOR'));
    } else {
        $gantry = Gantry::instance();
        if ($modulesCandidate) {
            $gantry['admin.restricted'] = true;
        }
        $gantry['router'] = function ($c) {
            return new Router($c);
        };
    }

} catch (Exception $e) {
    $app->enqueueMessage(Text::sprintf($e->getMessage()), 'error');

    return;
}

if (isset($forbidden)) {
    throw $forbidden;
}

// Dispatch to the controller.
/** @var Router $router */
$router = $gantry['router'];
$router->dispatch();
