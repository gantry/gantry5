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

use Gantry\Component\Assignments\AssignmentFilter;
use Gantry\Component\Assignments\AssignmentsInterface;
use Gantry\Component\Config\CompiledConfig;
use Gantry\Component\Config\ConfigFileFinder;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class OutlineChooser
{
    protected $filter;
    protected $candidates;
    protected $page;

    public function __construct()
    {
        $this->filter = new AssignmentFilter;
        $this->candidates = $this->loadAssignments();
        $this->page = $this->getPage();
    }

    public function select($default = 'default')
    {
        $scores = $this->scores();

        return key($scores) ?: $default;
    }

    public function scores()
    {
        return $this->filter->scores($this->candidates, $this->page);
    }

    public function matches()
    {
        return $this->filter->matches($this->candidates, $this->page);
    }

    public static function types()
    {
        $types = [
            'page'
        ];

        return $types;
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

        $config = new CompiledConfig($cache, [$files], GANTRY5_ROOT);

        return $config->load()->toArray();
    }

    public function getPage()
    {
        $list = [];

        foreach(self::types() as $type) {
            $class = '\Gantry\Grav\Assignments\Assignments' . ucfirst($type);

            if (!class_exists($class)) {
                throw new \RuntimeException("Assignment type {$type} is missing");
            }

            /** @var AssignmentsInterface $instance */
            $instance = new $class;
            $list[$type] = $instance->getRules();
            unset($instance);
        }

        return $list;
    }
}
