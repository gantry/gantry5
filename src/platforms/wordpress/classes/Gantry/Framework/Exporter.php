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

use Gantry\WordPress\Widgets;

/**
 * Class Exporter
 * @package Gantry\Framework
 */
class Exporter
{
    /**
     * @return array
     */
    public function positions()
    {
        return Widgets::export();
    }
}
