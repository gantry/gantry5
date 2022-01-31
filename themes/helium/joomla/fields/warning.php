<?php

/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Class JFormFieldWarning
 */
class JFormFieldWarning extends JFormField
{
    /** @var string */
    protected $type = 'Warning';

    /**
     * @return string
     * @throws Exception
     */
    protected function getInput()
    {
        $app = Factory::getApplication();
        if ($app->isClient('administrator')) {
            $app->enqueueMessage(Text::_('GANTRY5_THEME_INSTALL_GANTRY'), 'error');
        } else {
            $app->enqueueMessage(Text::_('GANTRY5_THEME_FRONTEND_SETTINGS_DISABLED'), 'warning');
        }

        return '';
    }
}
