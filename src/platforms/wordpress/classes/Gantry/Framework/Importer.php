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
 * Class Importer
 * @package Gantry\Framework
 */
class Importer
{
    /**
     * @param array $data
     */
    public function positions(array $data)
    {
        Widgets::import($data);
    }
}
