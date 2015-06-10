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

abstract class AbstractAssignments implements AssignmentsInterface
{
    /**
     * Returns assignment rules that apply to the current page.
     *
     * @param array $assignments
     * @param array $rules
     * @return array
     */
    public function getMatches(array &$assignments, array &$rules)
    {
        return \array_intersect_key($assignments, $rules);
    }

    /**
     * Returns the calculated score for the assignment.
     *
     * @param array $matches
     * @param string $method
     * @return int
     */
    public function getScore(array &$matches, $method = 'max')
    {
        $method = 'calc' . ucfirst($method);

        if (!method_exists($this, $method)) {
            return 0 + !empty($matches);
        }

        return array_reduce($matches, [$this, $method]);
    }

    /**
     * @param int|float $carry
     * @param int|float $item
     * @return int|float
     * @internal
     */
    protected function calcMin($carry, $item)
    {
        return min($carry, $item);
    }

    /**
     * @param int|float $carry
     * @param int|float $item
     * @return int|float
     * @internal
     */
    protected function calcMax($carry, $item)
    {
        return max($carry, $item);
    }

    /**
     * @param int|float $carry
     * @param int|float $item
     * @return int|float
     * @internal
     */
    protected function calcSum($carry, $item)
    {
        return $carry + $item;
    }

    /**
     * @param int|float $carry
     * @param int|float $item
     * @return int|float
     * @internal
     */
    protected function calcMul($carry, $item)
    {
        return $carry * $item;
    }
}
