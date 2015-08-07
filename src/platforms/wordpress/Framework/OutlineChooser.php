<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Config\CompiledConfig;
use Gantry\Component\Config\ConfigFileFinder;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class OutlineChooser
{
    protected $method;

    public function select($default = 'default')
    {
        $scores = $this->scores();

        return key($scores) ?: $default;
    }

    public function scores()
    {
        $matches = $this->matches();

        $scores = [];
        foreach ($matches as $type => $candidate) {
            $scores[$type] = $this->getScore($candidate);
        }

        arsort($scores);

        return $scores;
    }

    public function matches()
    {
        $candidates = static::loadAssignments();
        $page = static::getPage();

        $matches = [];
        foreach ($candidates as $type => $candidate) {
            foreach ($candidate as $section => $list) {
                foreach ($list as $name => $rules) {
                    if (isset($page[$section][$name])) {
                        $match =\array_intersect_key($page[$section][$name], $rules);
                        if ($match) {
                            $matches[$type][$section][$name] = $match;
                        }
                    }
                }
            }
        }

        return $matches;
    }

    // TODO: We might want to make this list more dynamic.
    public static function types()
    {
        $types = [
            'context',
            'menu',
            'post',
            'taxonomy',
            'archive'
        ];

        return apply_filters('g5_assignments_types', $types);
    }

    public function loadAssignments()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        // Find all the assignment files.
        $paths = $locator->findResources("gantry-config://");
        $files = (new ConfigFileFinder)->locateFileInFolder('assignments', $paths);

        $cache = $locator->findResource('gantry-cache://theme/compiled/config', true, true);

        $config = new CompiledConfig($cache, [$files]);

        return $config->load();
    }

    public function getPage()
    {
        $list = [];

        foreach(self::types() as $type) {
            $class = '\Gantry\WordPress\Assignments\Assignments' . ucfirst($type);

            if (!class_exists($class)) {
                throw new \RuntimeException("Assignment type {$type} is missing");
            }

            $instance = new $class;
            $list[$type] = $instance->getRules();
            unset($instance);
        }

        do_action('g5_assignments_page', $list);

        return $list;
    }

    /**
     * Returns the calculated score for the assignment.
     *
     * @param array $matches
     * @param string $method
     * @return int
     */
    protected function getScore(array &$matches, $method = 'max')
    {
        $this->method = 'calc' . ucfirst($method);

        if (!method_exists($this, $this->method)) {
            $this->method = 'calcOr';
        }

        return $this->calcArray(null, $matches);
    }

    protected function calcArray($carry, $item)
    {
        if (is_array($item)) {
            return array_reduce($item, [$this, 'calcArray'], $carry);
        }

        $method = $this->method;
        return $this->{$method}($carry, $item);
    }

    /**
     * @param int|float $carry
     * @param int|float $item
     * @return int|float
     * @internal
     */
    protected function calcOr($carry, $item)
    {
        return (int) ($carry || $item);
    }

    /**
     * @param int|float $carry
     * @param int|float $item
     * @return int|float
     * @internal
     */
    protected function calcMin($carry, $item)
    {
        return isset($carry) ? min($carry, $item) : $item;
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
        return isset($carry) ? $carry * $item : $item;
    }
}
