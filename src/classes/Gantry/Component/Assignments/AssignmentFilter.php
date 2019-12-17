<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Assignments;

/**
 * Class AssignmentFilter
 * @package Gantry\Assignments
 */
class AssignmentFilter
{
    protected $method;

    /**
     * Return all matching candidates with their score. Candidates are ordered by their scores.
     *
     * @param array $candidates  In format of candidates[name][section][rule].
     * @param array $page        In format of page[section][rule].
     * @return array
     */
    public function scores(array &$candidates, array &$page, callable $function = null)
    {
        $matches = $this->matches($candidates, $page, $function);

        $scores = [];
        foreach ($matches as $type => $candidate) {
            $scores[$type] = $this->getScore($candidate) + (isset($candidate['language']) ? 0.01 : 0);
        }

        // Always return matches by score in alphabetical order.
        ksort($scores, SORT_STRING);
        arsort($scores, SORT_NUMERIC);

        return $scores;
    }

    /**
     * Returns all matching candidates with matching rules.
     *
     * @param array $candidates  In format of candidates[name][section][rule].
     * @param array $page        In format of page[section][rule].
     * @return array
     */
    public function matches(array $candidates, array &$page, callable $function = null)
    {
        $matches = [];
        foreach ($candidates as $type => $candidate) {
            if (!is_array($candidate)) {
                if ($candidate === true && $page) {
                    $matches[$type] = $page;
                }
                continue;
            }
            foreach ($candidate as $section => $list) {
                if (!is_array($list)) {
                    if ($list === true && !empty($page[$section])) {
                        $matches[$type][$section] = $page[$section];
                    }
                    continue;
                }
                foreach ($list as $name => $rules) {
                    if (!empty($page[$section][$name])) {
                        if (!is_array($rules)) {
                            $match = $rules === true ? $page[$section][$name] : [];
                        } else {
                            $match = \array_intersect_key($page[$section][$name], $rules);
                        }
                        if ($match) {
                            $matches[$type][$section][$name] = $match;
                        }
                    }
                }
            }
            if (isset($matches[$type]) && $function && call_user_func($function, $candidate, $matches[$type], $page) === false) {
                unset($matches[$type]);
            }
        }

        return $matches;
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
        $this->method = 'calc' . ucfirst($method);

        if (!method_exists($this, $this->method)) {
            $this->method = 'calcMax';
        }

        return $this->calcArray(0, $matches);
    }

    /**
     * @param float $carry
     * @param float|array $item
     * @return float
     * @internal
     */
    protected function calcArray($carry, $item)
    {
        if (is_array($item)) {
            return array_reduce($item, [$this, 'calcArray'], $carry);
        }

        $method = $this->method;
        return $this->{$method}($carry, (float) $item);
    }

    /**
     * @param float $carry
     * @param float $item
     * @return float
     * @internal
     */
    protected function calcOr($carry, $item)
    {
        return (float) ($carry || $item);
    }

    /**
     * @param float $carry
     * @param float $item
     * @return float
     * @internal
     */
    protected function calcMin($carry, $item)
    {
        return $carry ? min($carry, $item) : $item;
    }

    /**
     * @param float $carry
     * @param float $item
     * @return float
     * @internal
     */
    protected function calcMax($carry, $item)
    {
        return max($carry, $item);
    }

    /**
     * @param float $carry
     * @param float $item
     * @return float
     * @internal
     */
    protected function calcSum($carry, $item)
    {
        return $carry + $item;
    }

    /**
     * @param float $carry
     * @param float $item
     * @return float
     * @internal
     */
    protected function calcMul($carry, $item)
    {
        return $carry ? $carry * $item : $item;
    }
}
