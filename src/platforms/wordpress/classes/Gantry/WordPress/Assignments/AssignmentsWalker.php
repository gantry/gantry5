<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 * Contains WordPress core code.
 */

namespace Gantry\WordPress\Assignments;

/**
 * Class AssignmentsWalker
 * @package Gantry\WordPress\Assignments
 */
class AssignmentsWalker
{
    var $tree_type = ['post_type', 'taxonomy', 'custom'];
    var $db_fields = ['parent' => 'parent_id', 'id' => 'id'];

    /**
     * Traverse elements to create list from elements.
     *
     * Adds one element if the element doesn't have any children otherwise,
     * add the element and its children. Will only traverse up to the max
     * depth and no ignore elements under that depth. It is possible to set the
     * max depth to include all depths, see walk() method.
     *
     * This method should not be called directly, use the walk() method instead.
     *
     * @param object $element           Data object.
     * @param array  $children_elements List of elements to continue traversing.
     * @param int    $max_depth         Max depth to traverse.
     * @param int    $depth             Depth of current element.
     * @param array  $args              An array of arguments.
     * @param string $output            Passed by reference. Used to append additional content.
     * @return null Null on failure with no changes to parameters.
     */
    public function add_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {

        if ( !$element )
            return;

        $id_field = $this->db_fields['id'];
        $id = $element->$id_field;

        //display this element
        $this->has_children = ! empty( $children_elements[ $id ] );
        if ( isset( $args[0] ) && is_array( $args[0] ) ) {
            $args[0]['has_children'] = $this->has_children; // Backwards compatibility.
        }

        $element->level = $depth;
        if($this->has_children) {
            $element->children = [];
            foreach($children_elements[ $id ] as $child) {
                $element->children[] = $child;
            }
            unset($child);
        }

        $output[] = $element;

        // descend only when the depth is right and there are childrens for this element
        if ( ($max_depth == 0 || $max_depth > $depth+1 ) && isset( $children_elements[$id]) ) {
            foreach( $children_elements[ $id ] as $child ){
                $this->add_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
            }
            unset( $children_elements[ $id ] );
        }
    }

    /**
     * Prepare array of elements hierarchically.
     *
     * Does not assume any existing order of elements.
     *
     * $max_depth = -1 means flatly display every element.
     * $max_depth = 0 means display all levels.
     * $max_depth > 0 specifies the number of display levels.
     *
     * @param array $elements  An array of elements.
     * @param int   $max_depth The maximum hierarchical depth.
     * @param array $walker_args
     * @return array The array of items with their depths and their direct children.
     */
    public function walk($elements, $max_depth, $walker_args = []) {

        $args = array_slice(func_get_args(), 2);
        $output = [];

        if ($max_depth < -1) //invalid parameter
            return $output;

        if (empty($elements)) //nothing to walk
            return $output;

        $parent_field = $this->db_fields['parent'];

        // flat display
        if ( -1 == $max_depth ) {
            $empty_array = array();
            foreach ( $elements as $e )
                $this->add_element( $e, $empty_array, 1, 0, $args, $output );
            return $output;
        }

        /*
         * Need to display in hierarchical order.
         * Separate elements into two buckets: top level and children elements.
         * Children_elements is two dimensional array, eg.
         * Children_elements[10][] contains all sub-elements whose parent is 10.
         */
        $top_level_elements = array();
        $children_elements  = array();
        foreach ( $elements as $e) {
            if ( 0 == $e->$parent_field )
                $top_level_elements[] = $e;
            else
                $children_elements[ $e->$parent_field ][] = $e;
        }

        /*
         * When none of the elements is top level.
         * Assume the first one must be root of the sub elements.
         */
        if ( empty($top_level_elements) ) {

            $first = array_slice( $elements, 0, 1 );
            $root = $first[0];

            $top_level_elements = array();
            $children_elements  = array();
            foreach ( $elements as $e) {
                if ( $root->$parent_field == $e->$parent_field )
                    $top_level_elements[] = $e;
                else
                    $children_elements[ $e->$parent_field ][] = $e;
            }
        }

        foreach ( $top_level_elements as $e )
            $this->add_element( $e, $children_elements, $max_depth, 0, $args, $output );

        /*
         * If we are displaying all levels, and remaining children_elements is not empty,
         * then we got orphans, which should be displayed regardless.
         */
        if ( ( $max_depth == 0 ) && count( $children_elements ) > 0 ) {
            $empty_array = array();
            foreach ( $children_elements as $orphans )
                foreach( $orphans as $op )
                    $this->add_element( $op, $empty_array, 1, 0, $args, $output );
        }

        return $output;
    }
}
