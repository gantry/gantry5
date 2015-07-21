<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

class Assignments
{
    public function __construct()
    {
    }

    public function get()
    {
        return [];
    }

    public function set($data)
    {
    }

    public function types()
    {
        return [];
    }

    public function getAssignment()
    {
        return 'default';
    }

    public function setAssignment($value)
    {
        throw new \RuntimeException('Not implemented');
    }

    public function assignmentOptions()
    {
        return [];
    }
}
