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

class AssignmentsMenu
{
    public $type = 'menu';

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        return [[]];
    }

    /**
     * List all the rules available.
     *
     * @return array
     */
    public function listRules()
    {
        // Get all defined menus
        $menus = $this->getMenus();

        // Return if there are no menus.
        if(!$menus) {
            return [];
        }

        // Get label and items for each menu
       $list = [];
        foreach ($menus as $menu) {
            $list[$menu->slug]['label'] = 'Menu - ' . $menu->name;
            $list[$menu->slug]['items'] = $this->getItems($menu);
        }

        return $list;
    }

    protected function getMenus($args = [])
    {
        $defaults = [
            'orderby' => 'name'
        ];

        $args = wp_parse_args($args, $defaults);

        $menus = wp_get_nav_menus(apply_filters('g5_assignments_get_menus_args', $args));

        return $menus;
    }

    protected function getItems($menu)
    {
        $items = [];

        // Get all items for the current menu
        if ($menu && !is_wp_error($menu)) {
            $menu_items = wp_get_nav_menu_items($menu->term_id);
        }

        // Check if the menu is not empty
        if(!$menu_items) {

            $items[] = [
                'name'     => '',
                'label'    => 'No items',
                'disabled' => true
            ];

        } else {

            $walker = new AssignmentsWalker;

            $new_menu_items = [];

            foreach($menu_items as $new_menu_item) {
                $new_menu_item->id           = $new_menu_item->ID;
                $new_menu_item->parent_id    = empty($new_menu_item->menu_item_parent) ? get_post_meta($new_menu_item->ID, '_menu_item_menu_item_parent', true) : $new_menu_item->menu_item_parent;
                $new_menu_items[] = $new_menu_item;
            }

            $menu_items = $walker->walk($new_menu_items, 0);

            foreach($menu_items as $menu_item) {
                $items[] = [
                    'name'     => $menu_item->ID,
                    'label'    => $menu_item->level > 0 ? str_repeat('—', $menu_item->level) . ' ' . $menu_item->title : $menu_item->title,
                    'disabled' => false
                ];
            }

        }

        return apply_filters('g5_assignments_' . $menu->slug . '_menu_list_items', $items, $menu->slug, $this->type);
    }

}
