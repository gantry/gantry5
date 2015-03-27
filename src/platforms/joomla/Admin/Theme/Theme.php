<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin\Theme;

use Gantry\Admin\Base\Theme as BaseTheme;

class Theme extends BaseTheme
{
    public function render($file, array $context = array())
    {
        // Add JavaScript Frameworks
        \JHtml::_('bootstrap.framework');

        return parent::render($file, $context);
    }
}
