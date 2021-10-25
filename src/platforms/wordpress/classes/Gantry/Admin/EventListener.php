<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Admin\Events\MenuEvent;
use Gantry\Component\Config\Config;
use Gantry\Component\Menu\Item;
use Gantry\Framework\Menu;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;

/**
 * Class EventListener
 * @package Gantry\Admin
 */
class EventListener implements EventSubscriberInterface
{
    /**
     * @return array
     */
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

    /**
     * @param Event $event
     */
    public function onGlobalSave(Event $event)
    {
        $option = (array) \get_option('gantry5_plugin');
        $option['production'] = (int)(bool) $event->data['production'];
        \update_option('gantry5_plugin', $option);
    }

    /**
     * @param Event $event
     */
    public function onStylesSave(Event $event)
    {
        $event->theme->preset_styles_update_css();
    }

    /**
     * @param Event $event
     */
    public function onSettingsSave(Event $event)
    {
    }

    /**
     * @param Event $event
     */
    public function onLayoutSave(Event $event)
    {
    }

    /**
     * @param Event $event
     */
    public function onAssignmentsSave(Event $event)
    {
    }

    /**
     * @param MenuEvent|Event $event
     */
    public function onMenusSave(Event $event)
    {
        /*
         * Automatically create navigation menu items:
         * https://clicknathan.com/web-design/automatically-create-wordpress-navigation-menu-items/
         *
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
         *
         * Example to handle custom menu items:
         * https://github.com/wearerequired/custom-menu-item-types/blob/master/inc/Custom_Menu_Items.php
         */

        $debug = [];

        /** @var Config $menu */
        $menu = $event->menu;

        // Each menu has ordering from 1..n counting all menu items. Children come right after parent ordering.
        $ordering = Menu::flattenOrdering($menu['ordering']);

        // Prepare menu items data.
        $items = Menu::prepareMenuItems($menu['items'], $menu['ordering'], $ordering);

        // Create database id map to detect moved/deleted menu items.
        $map = [];
        foreach ($items as $path => $item) {
            if (!empty($item['id'])) {
                $map[$item['id']] = $path;
            }
        }

        $menus = array_flip($event->gantry['menu']->getMenus());
        $menuId = isset($menus[$event->resource]) ? $menus[$event->resource] : 0;

        // Save global menu settings into Wordpress.
        $menuObject = \wp_get_nav_menu_object($menuId);
        if (\is_wp_error($menuObject)) {
            throw new \RuntimeException("Saving menu failed: Menu {$event->resource} ({$menuId}) not found", 400);
        }

        $options = [
            'menu-name' => trim(\esc_html($menu['settings.title']))
        ];

        $debug['update_menu'] = ['menu_id' => $menuId, 'options' => $options];

        if (Menu::WRITE_DB) {
            unset($menu['settings.title']);
            $menuId = \wp_update_nav_menu_object($menuId, $options);
            if (\is_wp_error($menuId)) {
                throw new \RuntimeException("Saving menu failed: Failed to update {$event->resource}", 400);
            }
        }

        // Get all menu items (or false).
        $unsorted_menu_items = \wp_get_nav_menu_items(
            $menuId,
            [
                'orderby' => 'ID',
                'output' => ARRAY_A,
                'output_key'  => 'ID',
                'post_status' => 'draft,publish'
            ]
        );

        $menu_items = [];
        if ($unsorted_menu_items) {
            foreach ($unsorted_menu_items as $item) {
                $menu_items[$item->db_id] = $item;
            }
        }
        unset($unsorted_menu_items);

        if (Menu::WRITE_DB) {
            \wp_defer_term_counting(true);
        }

        // Delete removed particles from the menu.
        foreach ($menu_items as $wpItem) {
            $path = isset($map[$wpItem->db_id]) ? $map[$wpItem->db_id] : '\\';
            if ($wpItem->type === 'custom' && !isset($items[$path]) && strpos($wpItem->attr_title, 'gantry-particle-') === 0) {
                $db_id = $wpItem->db_id;

                $debug['delete_' . $db_id] = ['id' => $db_id];

                if (Menu::WRITE_DB) {
                    \delete_post_meta($db_id, '_menu_item_gantry5');
                    \wp_delete_post($db_id);
                }
            }
        }

        $ignore = ['title', 'link', 'class', 'target', 'id'];
        $ignore_db = array_merge($ignore, ['object_id']);
        $list = [];

        foreach ($items as $key => $item) {
            // Add menu item defaults.
            $item += Item::$defaults;

            if (!empty($item['id']) && isset($menu_items[$item['id']])) {
                if (!empty($item['object_id'])) {
                    $item['object_id'] = (int)$item['object_id'];
                } else {
                    unset($item['object_id']);
                }
                $wpItem = $menu_items[$item['id']];
                $db_id = $wpItem->db_id;

                // Set parent and position.
                $parent_path = ltrim(dirname('/' . $key), '/\\');
                if ($parent_path) {
                    $parent = $items[$parent_path];
                    $parent_id = (int)$parent['id'];
                } else {
                    $parent_id = 0;
                }
                $position = $ordering[$key];

                $args = [
                    'menu-item-db-id' => $db_id,
                    'menu-item-object-id' => (int)$wpItem->object_id,
                    'menu-item-object' => $wpItem->object,
                    'menu-item-parent-id' => $parent_id,
                    'menu-item-position' => $position,
                    'menu-item-type' => $wpItem->type,
                    'menu-item-title' => \wp_slash(trim($item['title'])),
                    'menu-item-url' => $wpItem->url,
                    'menu-item-description' => $wpItem->description,
                    'menu-item-attr-title' => $wpItem->attr_title,
                    'menu-item-target' => $item['target'] !== '_self' ? $item['target'] : '',
                    'menu-item-classes' => \wp_slash(trim($item['class'])),
                    'menu-item-xfn' => $wpItem->xfn,
                    'menu-item-status' => $wpItem->status
                ];
                $meta = $this->normalizeMenuItem($item, $ignore_db);

                $debug['update_' . $key] = ['menu_id' => $menuId, 'id' => $db_id, 'args' => $args, 'meta' => $meta];

                if (Menu::WRITE_DB) {
                    \wp_update_nav_menu_item($menuId, $db_id, $args);
                    if (Menu::WRITE_META) {
                        \update_post_meta($db_id, '_menu_item_gantry5', \wp_slash(json_encode($meta)));
                    } else {
                        \delete_post_meta($db_id, '_menu_item_gantry5');
                    }
                }
            } elseif ($item['type'] === 'particle') {
                if (isset($item['parent_id']) && is_numeric($item['parent_id'])) {
                    // We have parent id available, use it.
                    $parent_id = $item['parent_id'];
                } else {
                    $parts = explode('/', $key);
                    $slug = array_pop($parts);
                    $parent_path = implode('/', $parts);
                    $parent_item = $parent_path && isset($items[$parent_path]) ? $items[$parent_path] : null;

                    $item['path'] = $key;
                    $item['route'] = (!empty($parent_item['route']) ? $parent_item['route'] . '/' : '') . $slug;

                    $wpItem = isset($parent_item['id'], $menu_items[$parent_item['id']]) ? $menu_items[$parent_item['id']] : null;
                    $parent_id = !empty($wpItem->db_id) ? $wpItem->db_id : 0;
                }

                // Create new particle menu item.
                $particle = isset($item['particle']) ? $item['particle'] : '';
                $args = [
                    'menu-item-db-id' => 0,
                    'menu-item-object-id' => 0,
                    'menu-item-object' => '',
                    'menu-item-parent-id' => $parent_id,
                    'menu-item-position' => isset($ordering[$key]) ? $ordering[$key] : 0,
                    'menu-item-type' => 'custom',
                    'menu-item-title' => \wp_slash(trim($item['title'])),
                    'menu-item-url' => '',
                    'menu-item-description' => '',
                    'menu-item-attr-title' => 'gantry-particle-' . $particle,
                    'menu-item-target' => $item['target'] !== '_self' ? $item['target'] : '',
                    'menu-item-classes' => \wp_slash(trim($item['class'])),
                    'menu-item-xfn' => ''
                ];
                $meta = $this->normalizeMenuItem($item, $ignore_db);

                $debug['create_' . $key] = ['menu_id' => $menuId, 'args' => $args, 'meta' => $meta, 'item' => $item];

                if (Menu::WRITE_DB) {
                    $db_id = \wp_update_nav_menu_item($menuId, 0, $args);
                    if ($db_id) {
                        // We need to update post_name to match the alias
                        \wp_update_nav_menu_item($menuId, $db_id, $args + ['menu-item-status' => 'publish']);
                        if (Menu::WRITE_META) {
                            \update_post_meta($db_id, '_menu_item_gantry5', \wp_slash(json_encode($meta)));
                        }
                    }
                }
            }

            // Add menu items for YAML file.
            $path = isset($item['yaml_path']) ? $item['yaml_path'] : $key;
            $meta = $this->normalizeMenuItem($item, $item['type'] !== 'particle' ? $ignore : []);
            $count = count($meta);
            // But only add menu items which have useful data in them.
            if ($count > 1 || ($count === 1 && !isset($meta['object_id']))) {
                $list[$path] = $meta;
            }
        }

        $menu['items'] = $list;

        $debug['yaml'] = $event->menu->toArray();
        $event->debug = $debug;

        if (!Menu::WRITE_YAML) {
            $event->save = false;
        }

        if (Menu::WRITE_DB) {
            unset($menu['items'], $menu['ordering']);
            \wp_defer_term_counting(false);
        }
    }

    /**
     * @param array $item
     * @param array $ignore
     * @return array
     */
    protected function normalizeMenuItem(array $item, array $ignore = [])
    {
        static $ignoreList = [
            // Never save derived values.
            'id', 'path', 'route', 'alias', 'parent_id', 'level', 'group', 'current', 'yaml_path', 'yaml_alias',
            // Also do not save WP variables we do not need.
            'rel', 'attr_title'
        ];

        if (isset($item['link_title'])) {
            $item['link_title'] = trim($item['link_title']);
        }

        if (isset($item['object_id'])) {
            $item['object_id'] = (int)$item['object_id'];
        }

        $item = Item::normalize($item, array_merge($ignore, $ignoreList));

        if (!isset($item['type']) || $item['type'] !== 'particle') {
            // These are storec into DB for non-particles.
            unset($item['title'], $item['link'], $item['type']);
        }

        return $item;
    }
}
