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

use Gantry\Component\Gantry\GantryTrait;
use Gantry\WordPress\Assignments\AssignmentsWalker;
use Gantry\WordPress\Assignments\AssignmentsContext;
use Gantry\WordPress\Assignments\AssignmentsMenu;
use Gantry\WordPress\Assignments\AssignmentsPost;
use Gantry\WordPress\Assignments\AssignmentsArchive;

class Assignments
{
    use GantryTrait;

    protected $style_id;

    public function __construct($style_id)
    {
        $this->style_id = $style_id;
    }

    public function get()
    {
        return $this->getTypes();
    }

    // TODO: We might want to make this list more dynamic.
    public function types()
    {
        $types = array(
            'context',
            'menu',
            'post',
//            'taxonomy',
            'archive'
        );

        return apply_filters('g5_assignments_types', $types);
    }

    public function getTypes()
    {
        $list = [];

        foreach($this->types() as $type) {
            $class = '\Gantry\WordPress\Assignments\Assignments' . ucfirst($type);

            if (!class_exists($class)) {
                throw new \RuntimeException("Assignment type {$type} is missing");
            }

            $instance = new $class;
            $list[$type] = $instance->listRules();
            unset($instance);
        }

        do_action('g5_assignments_list', $list);

        return $list;
    }
}
