<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

/**
 * Class Site
 * @package Gantry\Framework
 */
class Site extends \Timber\Site
{
    /**
     * @param string $widget_id
     * @return \TimberFunctionWrapper
     */
    public function sidebar( $widget_id = '' )
    {
        return \TimberHelper::function_wrapper('dynamic_sidebar', [$widget_id], true);
    }
}
