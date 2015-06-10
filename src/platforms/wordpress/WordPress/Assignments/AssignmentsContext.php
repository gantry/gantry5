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

class AssignmentsContext {
    var $type = 'context';

    public function getItems() {
        $items = [];

        $context = [
            'is_404'            => '404 Not Found Page',
            'is_search'         => 'Search Page',
            'is_tax'            => 'Taxonomy Archive',
            'is_front_page'     => 'Front Page',
            'is_home'           => 'Home Page',
            'is_attachment'     => 'Attachment Page',
            'is_single'         => 'Single Post',
            'is_page'           => 'Single Page',
            'is_category'       => 'Category Archive Page',
            'is_tag'            => 'Tag Archive Page',
            'is_author'         => 'Author Page',
            'is_date'           => 'Date Archive Page',
            'is_preview'        => 'Preview Page',
            'is_comments_popup' => 'Comments Popup Page'
        ];

        $context = apply_filters('g5_assignments_page_context_array', $context, $this->type);
        ksort($context);

        foreach($context as $conditional => $label) {
            $items[] = [
                'name'  => $this->type . '[' . $conditional . ']',
                'label' => $label
            ];
        }

        return apply_filters('g5_assignments_page_context_list_items', $items, $this->type);
    }

}
