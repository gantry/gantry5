<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;

class AssignmentsTaxonomy implements AssignmentsInterface
{
    public $type = 'taxonomy';
    public $label = 'Taxonomies: %s';
    public $priority = 8;

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        $rules = [];

        $queried_object = get_queried_object();

        if((is_archive() || is_tax()) && $queried_object !== null) {
            if(isset($queried_object->taxonomy) && isset($queried_object->term_id)) {
                $taxonomy = $queried_object->taxonomy;
                $id = $queried_object->term_id;

                $rules[$taxonomy][$id] = $this->priority;
            }
        }

        return $rules;
    }

    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function listRules($configuration)
    {
        $taxonomies = $this->getTaxonomies();

        if (!$taxonomies) {
            return [];
        }

        // Get label and items for each taxonomy
        $list = [];
        foreach($taxonomies as $tax) {
            $tax = apply_filters('g5_assignments_' . $this->type . '_' . $tax->name . '_taxonomy_object', $tax);

            $list[$tax->name]['label'] = sprintf($this->label, $tax->labels->name);
            $list[$tax->name]['items'] = $this->getItems($tax);
        }

        return $list;
    }

    protected function getTaxonomies($args = [])
    {
        $defaults = [
            'show_ui' => true
        ];

        $args = wp_parse_args($args, $defaults);

        $taxonomies = get_taxonomies(apply_filters('g5_assignments_get_taxonomies_args', $args), 'object');

        return $taxonomies;
    }

    protected function getItems($tax, $args = [])
    {
        $items = [];

        $defaults = [
            'child_of'                 => 0,
            'exclude'                  => '',
            'hide_empty'               => false,
            'hierarchical'             => 1,
            'include'                  => '',
            'include_last_update_time' => false,
            'order'                    => 'ASC',
            'orderby'                  => 'name',
            'pad_counts'               => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $terms = get_terms($tax->name, $args);

        if(empty($terms) || is_wp_error($terms)) {
            $items[] = [
                'name'     => '',
                'label'    => 'No items',
                'disabled' => true
            ];
        } else {
            $walker = new AssignmentsWalker;

            $new_terms = [];
            foreach($terms as $new_term) {
                $new_term->id           = $new_term->term_id;
                $new_term->parent_id    = $new_term->parent;
                $new_terms[] = $new_term;
            }

            $terms = $walker->walk($new_terms, 0);

            foreach($terms as $term) {
                $items[] = [
                    'name'     => $term->term_id,
                    'label'    => $term->level > 0 ? str_repeat('—', max(0, $term->level)) . ' ' . $term->name : $term->name,
                    'disabled' => false
                ];
            }
        }

        return apply_filters('g5_assignments_' . $this->type . '_' . $tax->name . '_taxonomy_list_items', $items, $tax, $this->type);

    }

}
