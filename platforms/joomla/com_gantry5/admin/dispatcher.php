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
        $identity = $this->app->getIdentity();

        // Check the user has permission to access this component if in the backend
        if ($this->app->isClient('administrator')
            && !$identity->authorise('core.manage', $this->option)
            && !$identity->authorise('core.manage', 'com_templates')
            && !($identity->authorise('core.manage', 'com_modules') && $this->input->request->getString('format') === 'json')
        )
        {
            throw new NotAllowed($this->app->getLanguage()->_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    /**
     * Dispatch a controller task. Redirecting the user if appropriate.
     *
     * @return  void
     * @throws NotAllowed
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
            $this->app->enqueueMessage(
                Text::sprintf('COM_GANTRY5_PLUGIN_MISSING', Text::_('COM_GANTRY5')),
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
            $this->app->enqueueMessage(
                JText::sprintf($e->getMessage()),
                'error'
            );
            return;
        }

        // Dispatch to the controller.
        $gantry['router']->dispatch();
    }
}