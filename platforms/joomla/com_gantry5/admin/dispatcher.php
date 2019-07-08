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

use Gantry\Admin\Router;
use Gantry\Framework\Gantry;
use Gantry5\Loader;
use Joomla\CMS\Dispatcher\Dispatcher;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Access\Exception\NotAllowed;

/**
 * Dispatcher class for com_gantry5
 *
 * @since 5.5.0
 */
class Gantry5Dispatcher extends Dispatcher
{
    /**
     * The extension namespace
     *
     * @var    string
     *
     * @since 5.5.0
     */
    protected $namespace = 'Joomla\\Component\\Gantry5';

    /**
     * Method to check component access permission
     *
     * @return  void
     *
     * @since   5.5.0
     */
    protected function checkAccess()
    {
        $application = $this->getApplication();
        $identity = $application->getIdentity();

        // Check the user has permission to access this component if in the backend
        if (!$identity || (
                $application->isClient('administrator')
                && !$identity->authorise('core.manage', 'com_gantry5')
                && !$identity->authorise('core.manage', 'com_templates')
                && !($identity->authorise('core.manage', 'com_modules')
                // FIXME: Joomla 4
                && $this->input->request->getString('format') === 'json')
            )
        )
        {
            throw new NotAllowed($application->getLanguage()->_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    /**
     * Dispatch a controller task. Redirecting the user if appropriate.
     *
     * @return  void
     * @throws NotAllowed
     * @throws \Whoops\Exception\ErrorException
     *
     * @since   5.5.0
     */
    public function dispatch()
    {
        // Check component access permission
        $this->checkAccess();

        if (!defined('GANTRYADMIN_PATH')) {
            define('GANTRYADMIN_PATH', JPATH_COMPONENT_ADMINISTRATOR);
        }

        // Detect Gantry Framework or fail gracefully.
        if (!class_exists('Gantry5\Loader')) {
            $this->getApplication()->enqueueMessage(
                Text::sprintf('COM_GANTRY5_PLUGIN_MISSING', Text::_('COM_GANTRY5')),
                'error'
            );
            return;
        }

        // Initialize administrator or fail gracefully.
        try {
            Loader::setup();

            $gantry = Gantry::instance();
            $gantry['router'] = function ($c) {
                return new Router($c);
            };

        } catch (Exception $e) {
            $this->getApplication()->enqueueMessage(Text::sprintf($e->getMessage()), 'error');
            return;
        }

        // Dispatch to the controller.
        /** @var Router $router */
        $router = $gantry['router'];
        $router->dispatch();
    }
}