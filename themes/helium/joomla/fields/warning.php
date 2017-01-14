<?php
/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

class JFormFieldWarning extends JFormField
{
    protected $type = 'Warning';

    protected function getInput()
    {
        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            $app->enqueueMessage(JText::_('GANTRY5_THEME_INSTALL_GANTRY'), 'error');
        } else {
            $app->enqueueMessage(JText::_('GANTRY5_THEME_FRONTEND_SETTINGS_DISABLED'), 'warning');
        }
    }
}