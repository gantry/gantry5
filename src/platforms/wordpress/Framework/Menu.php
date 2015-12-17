<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Gantry\GantryTrait;
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
        $groups = array();

        $menus = $this->getMenus();

        foreach ($menus as $menu) {
            // Initialize the group.
            $groups[$menu] = array();

            $items = $this->getItemsFromPlatform(['menu' => $menu]);

            // Build the groups arrays.
            foreach ($items as $item) {
                // Build the options array.
                $groups[$menu][$item->db_id] = [
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
     * @param int $levels
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
                $tree = $menuItem->menu_item_parent ? $menuItems[$menuItem->menu_item_parent]->tree : [];
                $menuItem->level = count($tree);
                $menuItem->tree = array_merge($tree, [$menuItem->db_id]);
                $menuItem->path = implode('/', $menuItem->tree);
                $menuItems[$menuItem->db_id] = $menuItem;
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

    protected function buildList($menuItems, $tree = [])
    {
        $list = [];

        if (!$menuItems) {
            return $list;
        }

        foreach ($menuItems as $menuItem) {
            $menuItem->level = count($tree);
            $menuItem->tree = array_merge($tree, [$menuItem->db_id]);
            $menuItem->path = implode('/', $menuItem->tree);
            $list[$menuItem->db_id] = $menuItem;

            if ($menuItem->children) {
                $list += $this->buildList($menuItem->children, $menuItem->tree);
            }

            if ($menuItem->current) {
                $this->current = $menuItem->db_id;
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
            if (isset($itemRef['id']) && is_numeric($itemRef['id'])) {
                $itemMap[$itemRef['id']] = &$itemRef;
            }
        }
        $slugMap = [];

        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        foreach ($menuItems as $menuItem) {
            $parent = $menuItem->menu_item_parent;

            $slugPath = $this->getMenuSlug($menuItems, $menuItem->tree);
            $slugMap[$slugPath] = $menuItem->db_id;

            // TODO: Path is menu path to the current page..
            $tree = [];

            if (($start && $start > $menuItem->level+1)
                || ($end && $menuItem->level+1 > $end)
                || ($start > 1 && !in_array($menuItem->tree[$start - 2], $tree))) {
                continue;
            }

            // These params always come from WordPress.
            $itemParams = [
                'id' => $menuItem->db_id,
                'type' => $menuItem->type,
                'link' => is_admin() ? $menuItem->url : $menuItem->link(),
                // TODO: use
                'attr_title' => $menuItem->attr_title,
                'rel' => $menuItem->xfn,
                'level' => $menuItem->level + 1
            ];

            // Rest of the items will come from saved configuration.
            if (isset($itemMap[$menuItem->db_id])) {
                // ID found, use it.
                $itemParams += $itemMap[$menuItem->db_id];
            } elseif (isset($items[$slugPath])) {
                // Otherwise use the slug path.
                $itemParams += $items[$slugPath];
            }

            // And if not available in configuration, default to WordPress.
            $itemParams += [
                'title' => is_admin() ? $menuItem->title : $menuItem->title(),
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
