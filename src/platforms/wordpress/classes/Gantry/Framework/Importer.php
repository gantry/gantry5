<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
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
