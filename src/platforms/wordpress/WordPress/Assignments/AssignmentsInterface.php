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
     * Returns assignment rules that apply to the current page.
     *
     * @param array $assignments
     * @param array $rules
     * @return array
     */
    public function getMatches(array &$assignments, array &$rules);

    /**
     * Returns the calculated score for the assignment.
     *
     * @param array $matches
     * @param string $method
     * @return int
     */
    public function getScore(array &$matches, $method = 'max');

    /**
     * List all the rules available.
     *
     * @return array
     */
    public function listRules();
}
