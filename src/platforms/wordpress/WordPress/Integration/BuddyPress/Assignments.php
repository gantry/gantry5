<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 * Contains WordPress core code.
 */

namespace Gantry\WordPress\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;

/**
 * Class Assignments
 * @package Gantry\WordPress\Integration\BuddyPress
 */
class AssignmentsBuddyPress implements AssignmentsInterface
{
    public $type = 'buddypress';
    public $priority = 4;

    public $_active = false;
    protected $context = [];

    /**
     * AssignmentsBuddyPress constructor.
     */
    public function __construct()
    {
        $this->_active = is_plugin_active('buddypress/bp-loader.php');
        if($this->_active) {
            $components = \bp_core_get_components('all');
            foreach($components as $key => $val) {
                $this->context[$key] = $val['title'];
            }
        }
    }

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        $rules = [];

        if($this->_active) {
            foreach($this->context as $var => $label) {
                if(bp_is_current_component($var) === true) {
                    $rules[$var] = $this->priority;
                }
            }
        }

        return [$rules];
    }

    /**
     * List all the rules available.
     *
     * @return array
     */
    public function listRules()
    {
        // Get label and items for the context.
        $list = [
            'label' => 'BuddyPress',
            'items' => $this->getItems()
        ];

        return [$list];
    }

    protected function getItems()
    {
        $items = [];
        $context = $this->context;

        foreach($context as $conditional => $label) {
            $items[] = [
                'name'  => $conditional,
                'label' => $label
            ];
        }

        return $items;
    }

    /**
     * Add BuddyPress to the Page Context list
     *
     * @param $context
     *
     * @return array
     */
    public static function addPageContextItem($context)
    {
        if(is_array($context)) {
            $context['is_buddypress'] = 'BuddyPress Page';
        }

        return $context;
    }

    /**
     * Add BuddyPress conditional tag check to the rules
     *
     * @param $rules
     * @param $priority
     *
     * @return array
     */
    public static function addPageContextConditionals($rules, $priority = 1)
    {
        if(!isset($rules)) {
            $rules = [];
        }

        $bp = buddypress();
        if(!bp_is_blog_page()) {
            $rules['is_buddypress'] = $priority;
        }

        return $rules;
    }
}
