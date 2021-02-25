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

use Gantry\Component\Assignments\AssignmentsInterface;

/**
 * Class Assignments
 * @package Gantry\WordPress\Integration\BuddyPress
 */
class AssignmentsBuddyPress implements AssignmentsInterface
{
    /** @var string */
    public $type = 'buddypress';
    /** @var int */
    public $priority = 4;
    /** @var bool */
    public $_active = false;

    /** @var array */
    protected $components = [];
    /** @var array */
    protected $member_types = [];
    /** @var array */
    protected $groups = [];

    public function __construct()
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $this->_active = \is_plugin_active('buddypress/bp-loader.php');

        if ($this->_active) {
            $components = \bp_core_get_components( 'all' );
            foreach ($components as $key => $val) {
                $this->components[$key] = $val['title'] . ' (' . $key . ')';
            }

            if (\bp_is_active('members')) {
                $member_types = \bp_get_member_types(array(), 'objects');
                foreach ($member_types as $member_type) {
                    $this->member_types[$member_type->name] = $member_type->labels['name'];
                }
            }

            if (\bp_is_active('groups')) {
                $groups = \groups_get_groups()['groups'];
                foreach ($groups as $group) {
                    $this->groups[$group->id] = $group->name;
                }
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
        $rules_components = [];
        $rules_member_types = [];
        $rules_groups = [];

        if ($this->_active) {
            foreach ($this->components as $var => $label) {
                if (\bp_is_current_component($var) === true) {
                    $rules_components[$var] = $this->priority;
                }
            }

            $user_id = \bp_loggedin_user_id();
            if ($user_id) {
                if (\bp_is_active('members')) {
                    $user_member_types = \bp_get_member_type($user_id, false);
                    if (!empty($user_member_types)) {
                        foreach ($this->member_types as $var => $label) {
                            if (in_array($var, $user_member_types, true)) {
                                $rules_member_types[$var] = $this->priority - 3;
                            }
                        }
                    }
                }

                if (\bp_is_active('groups')) {
                    $user_groups = \groups_get_user_groups($user_id)['groups'];
                    foreach ($this->groups as $var => $label) {
                        if (in_array($var, $user_groups, true)) {
                            $rules_groups[$var] = $this->priority - 3;
                        }
                    }
                }
            }
        }

        return [$rules_components, $rules_member_types, $rules_groups];
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
        $components = [
            'label' => 'BuddyPress Components',
            'items' => $this->getComponents(),
        ];

        if (\bp_is_active('members')) {
            $member_types = [
                'label' => 'BuddyPress Member Types',
                'items' => $this->getMemberTypes(),
            ];
        } else {
            $member_types = [];
        }

        if (\bp_is_active('groups')) {
            $groups = [
                'label' => 'BuddyPress Groups',
                'items' => $this->getGroups(),
            ];
        } else {
            $groups = [];
        }

        return [$components, $member_types, $groups];
    }

    /**
     * @return array
     */
    protected function getComponents()
    {
        $items = [];
        $context = $this->components;

        foreach($context as $conditional => $label) {
            $items[] = [
                'name'  => $conditional,
                'label' => $label
            ];
        }

        return $items;
    }

    /**
     * @return array
     */
    protected function getMemberTypes()
    {
        $items = [];
        $context = $this->member_types;

        foreach($context as $conditional => $label) {
            $items[] = [
                'name'  => $conditional,
                'label' => $label
            ];
        }

        return $items;
    }

    /**
     * @return array
     */
    protected function getGroups()
    {
        $items = [];
        $context = $this->groups;

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
        if (is_array($context)) {
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
        if (!isset($rules)) {
            $rules = [];
        }

        $bp = \buddypress();
        if (!\bp_is_blog_page()) {
            $rules['is_buddypress'] = $priority;
        }

        return $rules;
    }
}
