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
 * Class AssignmentsMenu
 * @package Gantry\WordPress\Assignments
 */
class AssignmentsMenu implements AssignmentsInterface
{
    /** @var string */
    public $type = 'menu';
    /** @var int */
    public $priority = 3;

    /**
     * Returns list of rules which apply to the current page.
     *
     * TODO: Make it smarter and not just recognize menu items by URL
     *
     * @return array
     */
    public function getRules()
    {
        $rules = [];

        $menus = $this->getMenus();

        if(!$menus) return [];

        foreach($menus as $menu) {
            if($menu && !\is_wp_error($menu)) {
                $menu_items = \wp_get_nav_menu_items($menu->term_id);
            }

            if($menu_items) {
                $current_url = $this->_curPageURL($_SERVER);

                if(\get_option('permalink_structure') && !\is_search()) {
                    $current_url = strtok($current_url, '?');
                }

                foreach($menu_items as $menu_item) {
                    if($menu_item->url === $current_url) {
                        $rules[$menu->slug][$menu_item->ID] = $this->priority;
                    }
                }
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

    /**
     * @param array $args
     * @return array
     */
    protected function getMenus($args = [])
    {
        $defaults = [
            'orderby' => 'name'
        ];

        $args = \wp_parse_args($args, $defaults);

        $menus = \wp_get_nav_menus(\apply_filters('g5_assignments_get_menus_args', $args));

        return $menus;
    }

    /**
     * @param object $menu
     * @return mixed
     */
    protected function getItems($menu)
    {
        $items = [];

        // Get all items for the current menu
        if ($menu && !\is_wp_error($menu)) {
            $menu_items = \wp_get_nav_menu_items($menu->term_id);
        }

        // Check if the menu is not empty
        if(!$menu_items) {
            /*
            $items[] = [
                'name'     => '',
                'label'    => 'No items',
                'disabled' => true
            ];
            */
        } else {

            $walker = new AssignmentsWalker;

            $new_menu_items = [];

            foreach($menu_items as $new_menu_item) {
                $new_menu_item->id           = $new_menu_item->ID;
                $new_menu_item->parent_id    = empty($new_menu_item->menu_item_parent) ? \get_post_meta($new_menu_item->ID, '_menu_item_menu_item_parent', true) : $new_menu_item->menu_item_parent;
                $new_menu_items[] = $new_menu_item;
            }

            $menu_items = $walker->walk($new_menu_items, 0);

            foreach($menu_items as $menu_item) {
                $items[] = [
                    'name'     => $menu_item->ID,
                    'label'    => $menu_item->level > 0 ? str_repeat('—', max(0, $menu_item->level)) . ' ' . $menu_item->title : $menu_item->title,
                    'disabled' => false
                ];
            }

        }

        return \apply_filters('g5_assignments_' . $menu->slug . '_menu_list_items', $items, $menu->slug, $this->type);
    }

    /**
     * @param array $s
     * @param bool $use_forwarded_host
     * @return string
     */
    function _URLorigin($s, $use_forwarded_host = false)
    {
        $s_port = \apply_filters('gantry5_current_url_server_port', '80');
        $s_ssl_port = \apply_filters('gantry5_current_url_server_ssl_port', '443');

        $ssl      = (!empty($s['HTTPS']) && $s['HTTPS'] === 'on');
        $sp       = strtolower($s['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port     = $s['SERVER_PORT'];
        $port     = ((!$ssl && $port == $s_port) || ($ssl && $port == $s_ssl_port)) ? '' : ':' . $port;
        $host     = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
        $host     = isset($host) ? $host : $s['SERVER_NAME'] . $port;

        return $protocol . '://' . $host;
    }

    /**
     * @param array $s
     * @param bool $use_forwarded_host
     * @return string
     */
    function _curPageURL($s, $use_forwarded_host = false)
    {
        return $this->_URLorigin($s, $use_forwarded_host) . $s['REQUEST_URI'];
    }
}
