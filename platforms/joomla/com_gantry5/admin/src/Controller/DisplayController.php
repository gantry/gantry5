<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Component\Gantry5\Administrator\Controller;

use Gantry\Admin\Router;
use Gantry\Framework\Gantry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default controller for component.
 */
class DisplayController extends BaseController
{
    /**
     * {@inheritDoc}
     *
     * @return  boolean
     */
    public function display($cachable = false, $urlparams = []): bool
    {
        $user = $this->app->getIdentity();

        if (
            !$user
            || (
                !$user->authorise('core.manage', 'com_gantry5')
                && !$user->authorise('core.manage', 'com_templates')
                // Editing particle module makes AJAX call to Gantry component, but has restricted access to json only.
                && !($user->authorise('core.manage', 'com_modules')
                && strtolower($this->input->getCmd('format', 'html')) === 'json')
            )
        ) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

            return false;
        }

        if (!defined('GANTRYADMIN_PATH')) {
            define('GANTRYADMIN_PATH', JPATH_COMPONENT_ADMINISTRATOR);
        }

        try {
            if (!PluginHelper::isEnabled('system', 'gantry5')) {
                $this->app->enqueueMessage(
                    Text::sprintf('COM_GANTRY5_PLUGIN_MISSING', Text::_('COM_GANTRY5')),
                    'error'
                );

                return false;
            }

            $gantry = Gantry::instance();

            $gantry['router'] = function ($c): Router {
                return new Router($c);
            };
        } catch (\Exception $e) {
            $this->app->enqueueMessage(Text::sprintf($e->getMessage()), 'error');

            return false;
        }

        // Dispatch to the controller.
        /** @var Router $router */
        $router = $gantry['router'];
        $router->dispatch();

        return true;
    }
}
