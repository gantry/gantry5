<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;

/**
 * Class Menu
 * @package Gantry\Framework
 */
class Menu extends AbstractMenu
{
    const READ_META = true;
    const READ_YAML = true;
    const WRITE_DB = true;
    const WRITE_META = true;
    const WRITE_YAML = true;

    /** @var array */
    protected $menus;
    /** @var int|null */
    protected $object;
    /** @var array */
    protected $current = [];
    /** @var array */
    protected $active = [];
    /** @var bool */
    protected $dbMeta = false;

    /**
     * Return list of menus.
     *
     * @param  array $args
     * @return array
     */
    public function getMenus($args = [])
    {
        if($this->menus === null) {
            $defaults = [
                'orderby' => 'name'
            ];

            $menus = \wp_get_nav_menus(\apply_filters('g5_menu_get_menus_args', \wp_parse_args($args, $defaults)));

            $this->menus = [];
            foreach($menus as $menu) {
                $this->menus[$menu->term_id] = urldecode($menu->slug);
            }
        }

        return $this->menus;
    }

    /**
     * Return list of menus.
     *
     * @param  array $args
     * @return array
     */
    public function getMenuOptions($args = [])
    {
        $defaults = [
            'orderby' => 'name'
        ];

        $menus = \wp_get_nav_menus(\apply_filters('g5_menu_get_menus_args', \wp_parse_args($args, $defaults)));

        $list = [];
        foreach($menus as $menu) {
            $list[urldecode($menu->slug)] = urldecode($menu->name);
        }

        return $list;
    }

    /**
     * Used in menu configuration to display full list of menu items as options.
     *
     * @return array
     */
    public function getGroupedItems()
    {
        $groups = [];
        foreach ($this->getMenus() as $menu) {
            $instance = $this->instance(['menu' => $menu]);

            // Build the groups arrays.
            $groups[$menu] = [];
            foreach ($instance as $item) {
                // Build the options array.
                $groups[$menu][$item->id] = [
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
        if (null === $this->config) {
            $config = parent::config();

            $menus = array_flip($this->getMenus());
            if (isset($menus[$this->params['menu']])) {
                $menu = new \TimberMenu($menus[$this->params['menu']]);

                $config->set('settings.title', $menu->name);
            }
        }

        return $this->config;
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function isActive($item)
    {
        return isset($this->active[$item->id]);
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function isCurrent($item)
    {
        // WP supports multiple current menu items (same route).
        return isset($this->current[$item->id]);
    }

    /**
     * @return int|null
     */
    public function getCacheId()
    {
        if (\is_user_logged_in()) {
            return null;
        }

        return (string)($this->object ?: 0);
    }

    /**
     * Get base menu item.
     *
     * If itemid is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   string|int|null $itemid
     * @return  int|null
     */
    protected function calcBase($itemid = null)
    {
        if ($itemid !== '/') {
            return $itemid;
        }

        // Use current menu item or fall back to default menu item.
        $current = reset($this->current);

        return $current ?: $this->default;
    }

    /**
     * Get menu items from the platform.
     *
     * @param array $params
     * @return array|null    List of routes to the pages.
     */
    protected function getItemsFromPlatform($params)
    {
        $gantry = static::gantry();
        $menus = array_flip($gantry['menu']->getMenus());
        $id = isset($menus[$params['menu']]) ? $menus[$params['menu']] : 0;

        // Get the nav menu based on the requested menu.
        $menuObject = \wp_get_nav_menu_object($id);

        /*
        // Get the nav menu based on the theme_location.
        if (!$menuObject) {
            $themeLocation = isset($params['theme_location']) ? $params['theme_location'] : null;
            if ($themeLocation && ($locations = get_nav_menu_locations()) && isset($locations[$themeLocation])) {
                $menuObject = wp_get_nav_menu_object($locations[$themeLocation]);
            }
        }
        */
        if (!$menuObject || \is_wp_error($menuObject)) {
            return null;
        }

        // Get all menu items as a flat list.
        $items = \wp_get_nav_menu_items(
            $id,
            [
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'draft,publish',
                'output' => ARRAY_N,
                'update_post_term_cache' => false
            ]
        );

        return $items;
    }

    /**
     * @param \WP_Post[] $menuItems
     * @return Item[]
     */
    public function createMenuItems($menuItems)
    {
        // Flag current and active menu items.
        $current = \get_queried_object_id();

        // Create menu items from all WP menu items.
        $list = [];
        foreach ($menuItems as $wpItem) {
            $item = $this->createMenuItemFromPost($wpItem);
            $list[$item->id] = $item;

            // Check if user is in the current menu item.
            if ($current && $item->object_id === $current) {
                $this->object = $current;
                $this->current[$item->id] = $current;
                $this->active[$item->id] = $current;

                // Also mark all parent menu items active.
                while (1) {
                    $parent_id = $item->parent_id;
                    $item = isset($list[$parent_id]) ? $list[$parent_id] : null;
                    // Make the method safe against loops.
                    if (!$item || isset($this->active[$item->id])) {
                        break;
                    }
                    $this->active[$item->id] = $current;
                }
            }
        }

        return $list;
    }

    /**
     * @param \WP_Post[] $menuItems
     * @param array $items
     * @return Item[]
     */
    protected function bindMenuItems($menuItems, &$items)
    {
        // Create quick lookup maps to help on matching the WP menu items to Gantry menu items.
        $aliases = $this->buildAliases($menuItems);
        $object_map = [];
        $name_map = [];
        foreach ($aliases as $id => $alias) {
            $object_map[$alias['object']][$id] = $id;
            $name_map[$alias['name']][$id] = $id;
            $name_map[$alias['alias']][$id] = $id;
        }

        // Generate a menu id tree from menu items.
        $paths = $this->buildIdPath($menuItems);

        // Update WP id/object_id/parent_id information to the YAML items matching the WP menu items.
        $lookup = [];
        foreach ($items as $path => &$item) {
            // Initialize some values.
            $id = null;
            if (!isset($item['yaml_path'])) {
                $item['yaml_path'] = $path;
            } else {
                $path = $item['yaml_path'];
            }

            // First check if object_id is set. This information does not change in WP export/import.
            $object_id = isset($item['object_id']) ? $item['object_id'] : null;
            if ($object_id && isset($object_map[$object_id])) {
                // Object id matched!
                $matches = $object_map[$object_id];
                // TODO: guess which menu item should be used, but for now we just pick the first one.
                $id = reset($matches);
            } else {
                // Try to match Gantry menu path to the menu item.
                $route = trim(dirname("/{$path}"), '/\\');
                $slug = Gantry::basename($path);

                // We may already have parent path matched to WP menu item; using it allows us to narrow down choices.
                $parent = isset($lookup[$route]) ? $lookup[$route] : null;
                if ($parent) {
                    $item['parent_id'] = $parent['id'];
                }

                // Find all WP menu items matching the slug of the Gantry menu item.
                if (isset($name_map[$slug])) {
                    $matches = $name_map[$slug];
                    foreach ($matches as $test_id) {
                        // Use alias lookup table to find the menu item.
                        $test = $aliases[$test_id];
                        $parent_id = $test['parent'];
                        if ($parent_id) {
                            if ($parent && $parent_id === $parent['id']) {
                                // Menu item with parent found.
                                $id = $test['id'];
                                break;
                            }
                        } elseif (!$parent) {
                            // Menu item without parent found.
                            $id = $test['id'];
                            break;
                        }
                    }
                }
            }

            // Add missing data to the Gantry menu item.
            $tree = null;
            if ($id) {
                // Existing WP menu item.
                $tree = $paths[$id];
                foreach ($tree as &$alias) {
                    $alias = $aliases[$alias]['name'];
                }
                unset($alias);
                $item['id'] = $id;
                $item['parent_id'] = $aliases[$id]['parent'];
                $item['object_id'] = $aliases[$id]['object'];
            } elseif (isset($item['parent_id'], $paths[$item['parent_id']])) {
                // Custom with existing parent.
                $tree = $paths[$item['parent_id']];
                foreach ($tree as &$alias) {
                    $alias = $aliases[$alias]['name'];
                }
                unset($alias);
                $slug = Gantry::basename($path);
                $tree[$slug] = $slug;
            } else {
                // Unknown menu item.
                $tree = explode('/', $path);
                $tree = array_combine($tree, $tree);
            }
            $item['path'] = implode('/', $tree);
            $item['tree'] = $tree;
            $item['level'] = count($tree);

            // Add item to parent lookup.
            $lookup[$path] = &$item;
        }

        return $items;
    }

    /**
     * Creates quick lookup arrays for all the WP menu items.
     *
     * @param \WP_Post[] $menuItems
     * @return array
     */
    protected function buildAliases($menuItems)
    {
        $list = [];
        foreach ($menuItems as $item) {
            $id = $item->ID;
            $list[$id] = [
                'id' => (int)$id,
                'object' =>(int)$item->object_id,
                'parent' => (int)$item->menu_item_parent,
                'name' => $item->post_name,
                'alias' => $this->getMenuAlias($item->title)
            ];
        }

        return $list;
    }

    /**
     * @param \WP_Post[] $menuItems
     */
    protected function buildIdPath($menuItems)
    {
        // Build tree from the menu items.
        $tree = [];
        foreach ($menuItems as $item) {
            $id = $item->ID;
            if (!isset($tree[$id])) {
                $tree[$id] = [];
            }
            $tree[$item->menu_item_parent][$id] = &$tree[$id];
        }

        // And flatten the list.
        return isset($tree[0]) ? $this->flattenTree($tree[0], []) : [];
    }

    /**
     * @param array $tree
     * @param array $path
     * @return array
     */
    protected function flattenTree(array $tree, array $path)
    {
        $items = [];
        foreach ($tree as $id => $array) {
            $p = $path;
            $p[$id] = $id;
            $items[$id] = $p;
            if ($array) {
                $items += $this->flattenTree($array, $p);
            }
        }

        return $items;
    }

    /**
     * @param \WP_Post $post
     * @param array $item
     * @return Item
     */
    protected function createMenuItemFromPost($post)
    {
        // These properties always come from WordPress.
        $properties = [
            'id' => (int)$post->ID,
            'parent_id' => (int)$post->menu_item_parent,
            'object_id' => (int)$post->object_id,
            'type' => $post->type,
            'alias' => $post->post_name,
            'link' => $post->url,
            'link_title' => $post->attr_title,
            'rel' => $post->xfn
        ];

        if ($properties['parent_id'] === 0) {
            // Parent ID = 0 is the root.
            $properties['parent_id'] = '';
        }

        // Add properties from post meta `_menu_item_gantry5`.
        if (static::READ_META && isset($post->gantry)) {
            $this->dbMeta = true;
            $properties += $post->gantry;

            // Detect particle which is saved into the menu.
            if (isset($properties['particle'])) {
                $properties['type'] = 'particle';
                $properties['link'] = null;
            }
        }

        if ('custom' === $properties['type']) {
            if (strpos($properties['link_title'], 'gantry-particle-') === 0) {
                // Detect newly created particle instance and convert it to a particle.
                $properties['type'] = 'particle';
                $properties['link'] = null;
                $properties['particle'] = substr($properties['link_title'],  16);
                $properties['options'] = [
                    'particle' => ['enabled' => '0'],
                    'block' => ['extra' => []]
                ];
            } elseif ($properties['link'] === '' || $properties['link'] === '#') {
                // Gantry menu separator.
                $properties['type'] = 'separator';
                $properties['link'] = null;
            }
        }

        // Add properties which may be overridden by Gantry, but are always found in WP menu item.
        $properties += [
            'title' => html_entity_decode($post->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
            'target' => $post->target ?: '_self',
            'class' => trim(implode(' ', $post->classes))
        ];

        // YAML file path compatibility.
        $properties['yaml_alias'] = $this->getMenuAlias($properties['title']);

        return new Item($this, $properties);
    }

    /**
     * @param string $title
     * @return string
     */
    protected function getMenuAlias($title)
    {
        $alias = preg_replace('|[ /]|u', '-', $title);
        if (preg_match('|^[a-zA-Z0-9-_]+$|', $alias)) {
            $alias = \strtolower($alias);
        }

        return $alias;
    }

    /**
     * Get a list of the menu items.
     *
     * @param array $params
     * @param array $items
     */
    public function getList(array $params, array $items)
    {
        $isAjax = !empty($params['POST']);

        $menuItems = $this->getItemsFromPlatform($params);
        if ($menuItems === null) {
            return;
        }

        if (!$isAjax) {
            $this->bindMenuItems($menuItems, $items);
        }

        $menuItems = $this->createMenuItems($menuItems, $items);

        $start   = $params['startLevel'];
        $max     = $params['maxLevels'];
        $end     = $max ? $start + $max - 1 : 0;

        // Get base menu item for this menu (defaults to active menu item).
        $base = $params['base'];
        $this->base = $this->calcBase($base);
        if ($this->base && $base === $this->base) {
            $this->root = $this->base;
        } else {
            $keys = array_reverse(array_keys($this->active));
            if ($start > 1) {
                $this->root = isset($keys[$start - 2]) ? $keys[$start - 2] : -1;
            }
        }

        foreach ($menuItems as $item) {
            $parent = $this->offsetGet($item->parent_id);
            $level = $parent ? $parent->level + 1 : 100000;
            if ($end && $level > $end) {
                continue;
            }

            // Add menu item into the menu.
            $this->add($item);
            if ($item->link) {
                $item->url($item->link);
            }
        }

        if ($this->dbMeta) {
            // Disable custom ordering if using DB meta.
            $this->config()->set('ordering', []);
        }

        if (static::READ_YAML && $items) {
            $this->addCustom($params, $items);
        }
    }

    /**
     * Add custom menu items and properties from menu YAML.
     *
     * @param array $params
     * @param array $items
     */
    public function addCustom(array $params, array $items)
    {
        $isAjax = !empty($params['POST']);
        if (!$isAjax && $this->dbMeta) {
            return;
        }
        $config = $this->config();
        $type = $config->get('settings.type');

        // Add custom menu elements.
        foreach ($items as $route => $item) {
            if ($isAjax) {
                $object_id = $route;
                $route = null;
            } else {
                $object_id = isset($item['id']) ? $item['id'] : null;
            }

            $object = $this->getObject($object_id, $route);
            if ($object) {
                // Update properties which do not come from WordPress.
                foreach ($item as $key => $value) {
                    if (!in_array($key, ['id', 'parent_id', 'object_id', 'type', 'alias', 'link', 'link_title', 'rel'])) {
                        $object[$key] = $value;
                    }
                }
            } else {
                // Only add particles if menu isn't custom made.
                if ($type !== 'custom' && (!isset($item['type']) || $item['type'] !== 'particle')) {
                    continue;
                }

                if ($isAjax) {
                    $item = new Item($this, $item);
                    $this->add($item);
                } else {
                    $tree = explode('/', $route);
                    $parentTree = $tree;
                    $alias = array_pop($parentTree);
                    $parentRoute = implode('/', $parentTree);
                    $check = isset($items[$parentRoute]) ? $items[$parentRoute] : null;
                    $parent_id = isset($check['id']) ? $check['id'] : null;

                    $parent = $this->getObject($parent_id, $parentRoute);
                    if ($parent) {
                        // Enabled state should equal particle setting.
                        $item['enabled'] = !isset($item['options']['particle']['enabled']) || !empty($item['options']['particle']['enabled']);
                        $item['id'] = $route;
                        $item['parent_id'] = $parent->id;
                        $item['alias'] = $item['yaml_alias'] = $alias;
                        $item['level'] = \count($tree);

                        $item = new Item($this, $item);
                        $this->add($item);
                    }
                }
            }
        }
    }

    /**
     * @param int|null $id
     * @param string|null $route
     * @return Item|null
     */
    protected function getObject($id, $route = null)
    {
            // Check if menu item with the object id exists.
            $object = $id ? $this->__get($id) : null;

            // If not, fall back to the path.
            if (!$object && null !== $route) {
                $object = $this[$route];
            }

            return $object;
    }
}
