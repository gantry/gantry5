<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;

/**
 * Class AssignmentsContext
 * @package Gantry\WordPress\Assignments
 */
class AssignmentsContext implements AssignmentsInterface
{
    /** @var string */
    public $type = 'context';
    /** @var int */
    public $priority = 2;

    /** @var array */
    protected $priorities = [
        'is_front_page'     => 0.9,
        'is_home'           => 0.9,
    ];
    /** @var array */
    protected $context = [
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

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        global $wp_query;

        $rules = [];
        foreach($this->context as $var => $label) {
            if (isset($wp_query->{$var}) && $wp_query->{$var} === true) {
                $rules[$var] = $this->priority + (isset($this->priorities[$var]) ? $this->priorities[$var] : 0);
            }
        }

        // Workaround for when is_front_page is missing in the $wp_query
        if(\is_front_page() === true) {
            $rules['is_front_page'] = $this->priority + $this->priorities['is_front_page'];
        }

        // Allow to filter out rules by 3rd party plugin integration
        $rules = \apply_filters('g5_assignments_page_context_rules', $rules, $this->priority);

        return [$rules];
    }

    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function listRules($configuration)
    {
        // Get label and items for the context.
        $list = [
            'label' => 'Page Context',
            'items' => $this->getItems()
        ];

        return [$list];
    }

    protected function getItems()
    {
        $items = [];

        $context = \apply_filters('g5_assignments_page_context_array', $this->context, $this->type);
        ksort($context);

        foreach($context as $conditional => $label) {
            $items[] = [
                'name'  => $conditional,
                'label' => $label
            ];
        }

        return \apply_filters('g5_assignments_page_context_list_items', $items, $this->type);
    }

}
