<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;

class Menu extends AbstractMenu
{
    protected $menus;
    protected $wp_menu;
    protected $current;
    protected $active = [];

    public function __construct()
    {
        $this->menus = $this->getMenus();
    }

    /**
     * Return list of menus.
     *
     * @param  array $args
     * @return array
     */
    public function getMenus($args = [])
    {
        static $list;

        if($list === null) {
            $defaults = [
                'orderby' => 'name'
            ];

            $args = wp_parse_args($args, $defaults);
            $get_menus = wp_get_nav_menus(apply_filters('g5_menu_get_menus_args', $args));

            foreach($get_menus as $menu) {
                $list[$menu->term_id] = urldecode($menu->slug);
            }
        }

        return $list;
    }

    public function getGroupedItems()
    {
        $groups = [];

        $menus = (array) $this->getMenus();

        foreach ($menus as $menu) {
            // Initialize the group.
            $groups[$menu] = [];

            $items = (array) $this->getItemsFromPlatform(['menu' => $menu]);

            // Build the groups arrays.
            foreach ($items as $item) {
                // Build the options array.
                $groups[$menu][$item->ID] = [
                    'spacing' => str_repeat('&nbsp; ', max(0, $item->level)),
                    'label' => $item->title
                ];
            }
        }

        return $groups;
    }

    /**
     * Get menu configuration.
     *
     * @return Config
     */
    public function config()
    {
        if ($this->config) {
            return $this->config;
        }

        $config = parent::config();

        $menu = $this->getWPMenu($this->params);

        $config->set('settings.title', $menu->name);

        return $config;
    }

    protected function getWPMenu($params) {
        if (!isset($this->wp_menu)) {
            $menus = array_flip($this->getMenus());
            if (isset($menus[$params['menu']])) {
                $this->wp_menu = new \TimberMenu($menus[$params['menu']]);
            }
        }

        return $this->wp_menu;
    }

    /**
     * Get menu items from the platform.
     *
     * @param array $params
     * @return array    List of routes to the pages.
     */
    protected function getItemsFromPlatform($params)
    {
        if (is_admin()) {
            $gantry = static::gantry();
            $menus = array_flip($gantry['menu']->getMenus());
            $id = isset($menus[$params['menu']]) ? $menus[$params['menu']] : 0;

            // Save global menu settings into Wordpress.
            $menuObject = wp_get_nav_menu_object($id);
            if (is_wp_error($menuObject)) {
                return null;
            }

            // Get all menu items.
            $unsorted_menu_items = wp_get_nav_menu_items(
                $id,
                ['post_status' => 'draft,publish']
            );

            $menuItems = [];
            foreach ($unsorted_menu_items as $menuItem) {
                $menuItems[$menuItem->ID] = $menuItem;
            }

            foreach ($menuItems as $menuItem) {
                $this->updateMenuItem($menuItem, $menuItems);
            }

            return $menuItems;
        }

        $menu = $this->getWPMenu($params);

        if ($menu) {
            return $this->buildList($menu->get_items());
        }

        return null;
    }

    public function isActive($item)
    {
        return isset($this->active[$item->id]);
    }

    /**
     * @return string|null
     */
    public function getCacheId()
    {
        if (is_user_logged_in()) {
            return null;
        }

        return $this->current ?: 0;
    }

    public function isCurrent($item)
    {
        return $this->current == $item->id;
    }

    /**
     * Get base menu item.
     *
     * If itemid is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   int  $itemid
     *
     * @return  object|null
     */
    protected function calcBase($itemid = null)
    {
        // Use current menu item or fall back to default menu item.
        $base = $this->current ?: $this->default;

        // Return base menu item.
        return $base;
    }

    protected function updateMenuItem($item, array $items)
    {
        if (!empty($item->menu_item_parent) && isset($items[$item->menu_item_parent])) {
            $parent = $items[$item->menu_item_parent];
            if (!isset($parent->tree)) {
                $this->updateMenuItem($parent, $items);
            }

            $tree = $parent->tree;
        } else {
            $tree = [];
        }
        $item->level = count($tree);
        $item->tree = array_merge($tree, [$item->ID]);
        $item->path = implode('/', $item->tree);
    }

    protected function buildList($menuItems, $tree = [])
    {
        $list = [];

        if (!$menuItems) {
            return $list;
        }

        foreach ($menuItems as $menuItem) {
            $menuItem->level = count($tree);
            $menuItem->tree = array_merge($tree, [$menuItem->ID]);
            $menuItem->path = implode('/', $menuItem->tree);
            $list[$menuItem->ID] = $menuItem;

            if ($menuItem->children) {
                $list += $this->buildList($menuItem->children, $menuItem->tree);
            }

            if ($menuItem->current) {
                $this->current = $menuItem->ID;
                $this->active += array_flip($menuItem->tree);
            }
        }

        return $list;
    }

    protected function getMenuSlug(array &$menuItems, $tree)
    {
        $result = [];
        foreach ($tree as $id) {
            if (!isset($menuItems[$id])) {
                throw new \RuntimeException("Menu item parent ($id) cannot be found");
            }
            $slug = is_admin() ? $menuItems[$id]->title : $menuItems[$id]->title();
            $slug = preg_replace('|[ /]|u', '-', $slug);
            if (preg_match('|^[a-zA-Z0-9-_]+$|', $slug)) {
                $slug = \strtolower($slug);
            }
            $result[] = $slug;
        }

        return implode('/', $result);
    }

    /**
     * Get a list of the menu items.
     *
     * @param  array  $params
     * @param  array  $items
     */
    public function getList(array $params, array $items)
    {
        $start   = $params['startLevel'];
        $max     = $params['maxLevels'];
        $end     = $max ? $start + $max - 1 : 0;

        $menuItems = $this->getItemsFromPlatform($params);
        if($menuItems === null) return;

        $itemMap = [];
        foreach ($items as $path => &$itemRef) {
            if (isset($itemRef['object_id']) && is_numeric($itemRef['object_id'])) {
                $itemRef['path'] = $path;
                $itemMap[$itemRef['object_id']] = $itemRef;
            }
        }
        $slugMap = [];

        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        foreach ($menuItems as $menuItem) {
            $slugPath = $this->getMenuSlug($menuItems, $menuItem->tree);
            $slugMap[$slugPath] = $menuItem->ID;

            // TODO: Path is menu path to the current page..
            $tree = [];

            if (($start && $start > $menuItem->level+1)
                || ($end && $menuItem->level+1 > $end)
                || ($start > 1 && !in_array($menuItem->tree[$start - 2], $tree))) {
                continue;
            }

            // These params always come from WordPress.
            $itemParams = [
                'id' => $menuItem->ID,
                'object_id' => $menuItem->object_id,
                'type' => $menuItem->type,
                'link' => is_admin() ? $menuItem->url : $menuItem->link(),
                'link_title' => $menuItem->attr_title,
                'rel' => $menuItem->xfn,
                'level' => $menuItem->level + 1
            ];

            // Rest of the items will come from saved configuration.
            if (isset($itemMap[$menuItem->object_id])) {
                // ID found, use it.
                $itemParams += $itemMap[$menuItem->object_id];

                // Store new path for the menu item into path map.
                if ($slugPath !== $itemMap[$menuItem->object_id]['path']) {
                    if (!$this->pathMap) {
                        $this->pathMap = new Config([]);
                    }
                    $this->pathMap->set(preg_replace('|/|u', '/children/', $itemMap[$menuItem->object_id]['path']) . '/path', $slugPath, '/');
                }
            } elseif (isset($items[$slugPath])) {
                // Otherwise use the slug path.
                $itemParams += $items[$slugPath];
            }

            // And if not available in configuration, default to WordPress.
            $itemParams += [
                'title' => html_entity_decode(is_admin() ? $menuItem->title : $menuItem->title(), ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                'target' => $menuItem->target ?: '_self',
                'class' => implode(' ', $menuItem->classes)
            ];

            $item = new Item($this, $slugPath, $itemParams);
            $this->add($item);

            // Placeholder page.
            if ($item->type == 'custom' && $item->link == '#' || $item->link == '') {
                $item->type = 'separator';
            }

            switch ($item->type) {
                case 'separator':
                    // Separator and heading have no link.
                    $item->url(null);
                    break;

                case 'custom':
                    $item->url($item->link);
                    break;

                default:
                    $item->url($item->link);
                    break;
            }
        }
    }
}
