<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress\Assignments;

interface AssignmentsInterface
{
    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules();

    /**
     * List all the rules available.
     *
     * @return array
     */
    public function listRules();
}
