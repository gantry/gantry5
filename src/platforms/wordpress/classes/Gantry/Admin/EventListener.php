<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;

class EventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'admin.global.save' => ['onGlobalSave', 0],
            'admin.styles.save' => ['onStylesSave', 0],
            'admin.settings.save' => ['onSettingsSave', 0],
            'admin.layout.save' => ['onLayoutSave', 0],
            'admin.assignments.save' => ['onAssignmentsSave', 0],
            'admin.menus.save' => ['onMenusSave', 0]
        ];
    }

    public function onGlobalSave(Event $event)
    {
        $option = (array) \get_option('gantry5_plugin');
        $option['production'] = intval((bool) $event->data['production']);
        \update_option('gantry5_plugin', $option);
    }

    public function onStylesSave(Event $event)
    {
        $event->theme->preset_styles_update_css();
    }

    public function onSettingsSave(Event $event)
    {
    }

    public function onLayoutSave(Event $event)
    {
    }

    public function onAssignmentsSave(Event $event)
    {
    }

    public function onMenusSave(Event $event)
    {
        /*
         * Add widgets and particles to any menu:
         * http://www.wpbeginner.com/wp-themes/how-to-add-custom-items-to-specific-wordpress-menus/
         *
         * Skip menu items dynamically:
         * http://wordpress.stackexchange.com/questions/31748/dynamically-exclude-menu-items-from-wp-nav-menu
         *
         * Updating menu item (extra data goes to wp_postmeta table):
         *   get_post()
         *   wp_insert_post()
         *   wp_update_post($menu_item_data)
         *   register_post_type()
         *   update_post_meta()
         *
         * https://github.com/WordPress/WordPress/blob/master/wp-admin/nav-menus.php#L65
         */

        $defaults = [
            'id' => 0,
            'layout' => 'list',
            'target' => '_self',
            'dropdown' => '',
            'icon' => '',
            'image' => '',
            'subtitle' => '',
            'icon_only' => false,
            'visible' => true,
            'group' => 0,
            'columns' => [],
            'link_title' => '',
            'hash' => '',
            'class' => '',
            'anchor_class' => '',
            'enabled' => 1,
        ];

        $menu = $event->menu;

        $menus = array_flip($event->gantry['menu']->getMenus());
        $id = isset($menus[$event->resource]) ? $menus[$event->resource] : 0;

        // Save global menu settings into Wordpress.
        $menuObject = wp_get_nav_menu_object($id);
        if (is_wp_error($menuObject)) {
            throw new \RuntimeException("Saving menu failed: Menu {$event->resource} ({$id}) not found", 400);
        }

        $options = [
            'menu-name' => trim(esc_html($menu['settings.title']))
        ];

        $id = wp_update_nav_menu_object($id, $options);
        if (is_wp_error($id)) {
            throw new \RuntimeException("Saving menu failed: Failed to update {$event->resource}", 400);
        }

        unset($menu['settings']);

        // Get all menu items (or false).
        $unsorted_menu_items = wp_get_nav_menu_items(
            $id,
            ['orderby' => 'ID', 'output' => ARRAY_A, 'output_key' => 'ID', 'post_status' => 'draft,publish']
        );

        $menu_items = [];
        if ($unsorted_menu_items) {
            foreach ($unsorted_menu_items as $_item) {
                $menu_items[$_item->db_id] = $_item;
            }
        }

        wp_defer_term_counting(true);

        // Each menu has ordering from 1..n counting all menu items. Children come right after parent ordering.
        $ordering = $this->flattenOrdering($menu['ordering']);

        foreach ($menu['items'] as $key => $item) {
            if (!empty($item['id']) && isset($menu_items[$item['id']])) {
                if (!empty($item['object_id'])) {
                    $item['object_id'] = (int)$item['object_id'];
                } else {
                    unset($item['object_id']);
                }
                $wpItem = $menu_items[$item['id']];

                $args = [
                    'menu-item-db-id' => $wpItem->db_id,
                    'menu-item-object-id' => $wpItem->object_id,
                    'menu-item-object' => $wpItem->object,
                    'menu-item-parent-id' => $wpItem->menu_item_parent,
                    'menu-item-position' => isset($ordering[$key]) ? $ordering[$key] : 0,
                    'menu-item-type' => $wpItem->type,
                    'menu-item-title' => trim($item['title']),
                    'menu-item-url' => $wpItem->url,
                    'menu-item-description' => $wpItem->description,
                    'menu-item-attr-title' => $wpItem->attr_title,
                    'menu-item-target' => $item['target'] != '_self' ? $item['target'] : '',
                    'menu-item-classes' => trim($item['class']),
                    'menu-item-xfn' => $wpItem->xfn
                ];

                wp_update_nav_menu_item($id, $wpItem->db_id, $args);

                unset($item['title'], $item['link'], $item['class'], $item['target'], $item['type'], $item['id']);
            }

            $item['enabled'] = (int) $item['enabled'];

            // Do not save default values.
            foreach ($defaults as $var => $value) {
                if (isset($item[$var]) && $item[$var] == $value) {
                    unset($item[$var]);
                }
            }

            // Do not save derived values.
            unset($item['path'], $item['alias'], $item['parent_id'], $item['level'], $item['group'], $item['current']);

            // Do not save WP variables we do not use.
            unset($item['rel'], $item['attr_title']);

            // Particles have no link.
            if (isset($item['type']) && $item['type'] === 'particle') {
                unset($item['link']);
            }

            $event->menu["items.{$key}"] = $item;
        }

        wp_defer_term_counting(false);
    }

    protected function flattenOrdering(array $ordering, $parents = [], &$i = 0)
    {
        $list = [];
        $group = isset($ordering[0]);
        foreach ($ordering as $id => $children) {
            $tree = $parents;
            if (!$group && !preg_match('/^(__particle|__widget)/', $id)) {
                $tree[] = $id;
                $name = implode('/', $tree);
                $list[$name] = ++$i;
            }
            if (is_array($children)) {
                $list += $this->flattenOrdering($children, $tree, $i);
            }
        }

        return $list;
    }
}
