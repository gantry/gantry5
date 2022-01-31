<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Menu;

use Gantry\Component\Config\Config;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;
use RocketTheme\Toolbox\ArrayTraits\Countable;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\Iterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class AbstractMenu
 * @package Gantry\Component\Menu
 */
abstract class AbstractMenu implements \ArrayAccess, \Iterator, \Countable
{
    use GantryTrait, ArrayAccessWithGetters, Iterator, Export, Countable;

    /** @var int|string|null */
    public $id;

    /** @var array */
    protected $paths = [];
    /** @var array */
    protected $yaml_paths = [];
    /** @var string|int */
    protected $default;
    /** @var string */
    protected $root = '';
    /** @var string */
    protected $base;
    /** @var string */
    protected $active;
    /** @var array */
    protected $params;
    /** @var bool */
    protected $override = false;
    /** @var Config|null */
    protected $config;
    /** @var Item[] */
    protected $items;
    /** @var Config|null */
    protected $pathMap;
    /** @var array */
    protected $defaults = [
        'menu' => '',
        'base' => '/',
        'startLevel' => 1,
        'maxLevels' => 0,
        'showAllChildren' => true,
        'highlightAlias' => true,
        'highlightParentAlias' => true
    ];

    /**
     * Create ordering lookup index [path => 1...n] from the nested ordering. Lookup has been sorted by accending ordering.
     *
     * @param array $ordering Nested ordering structure.
     * @return array
     */
    public static function flattenOrdering(array $ordering)
    {
        $ordering = static::fixOrdering($ordering);
        $list = static::flattenOrderingRecurse($ordering);

        asort($list, SORT_NUMERIC);

        return $list;
    }

    /**
     * Prepare menu items data.
     *
     * @param array $items
     * @param array $ordering
     * @param array|null $orderMap
     * @return array
     */
    public static function prepareMenuItems(array $items, array $ordering, array $orderMap = null)
    {
        $ordering = static::fixOrdering($ordering);
        static::embedOrderingRecurse($items, $ordering);

        if (null === $orderMap) {
            $orderMap = static::flattenOrdering($ordering);
        }

        // Order menu items by their new ordering.
        $items = array_replace($orderMap, $items);
        foreach ($items as $key => $item) {
            if (!is_array($item)) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * @param array $ordering
     * @param array $parents
     * @param int $i
     * @return array
     */
    public static function flattenOrderingRecurse(array $ordering, $parents = [], &$i = 0)
    {
        if (!$ordering) {
            return [];
        }

        $list = [[]];
        $isGroup = isset($ordering[0]);
        foreach ($ordering as $path => $children) {
            $tree = $parents;
            if (!$isGroup) {
                $tree[] = Gantry::basename($path);
                $name = implode('/', $tree);
                $list[0][$name] = ++$i;
            }
            if (\is_array($children)) {
                $list[] = static::flattenOrderingRecurse($children, $tree, $i);
            }
        }

        return array_replace(...$list);
    }

    /**
     * @param array $items
     * @param array $ordering
     * @param array $parents
     * @param int $pos
     */
    protected static function embedOrderingRecurse(array &$items, array $ordering, $parents = [], $pos = 0)
    {
        $name = implode('/', $parents);
        $isGroup = isset($ordering[0]);
        if ($isGroup) {
            // Remove empty columns from the end of the list.
            do {
                $last = end($ordering);
                if ($last === []) {
                    array_pop($ordering);
                }
            } while ($last === []);

            // Make sure that ordering keys are 0...n.
            $ordering = array_values($ordering);

            // If there is only a single column, remove columns settings.
            if (count($ordering) < 2) {
                $ordering = isset($ordering[0]) ? $ordering[0] : [];
                $isGroup = false;
                $items[$name]['columns'] = [];
                $items[$name]['columns_count'] = [];
            }
        }

        $counts = [];
        foreach ($ordering as $path => $children) {
            $tree = $parents;
            $count = \is_array($children) ? \count($children) : 0;

            if ($isGroup) {
                $counts[] = $count;
            } else {
                $tree[] = Gantry::basename($path);
            }
            if (\is_array($children)) {
                static::embedOrderingRecurse($items, $children, $tree, $isGroup ? $pos : 0);

                $pos += $count;
            }
        }

        if ($isGroup) {
            $items[$name]['columns_count'] = $counts;
        }
    }

    /**
     * @param array $ordering
     * @return array
     */
    protected static function fixOrdering(array $ordering)
    {
        // FIXME: @djamil, if you move particle from column 2+, it breaks the main level.
        if (isset($ordering[0])) {
            $ordering = $ordering[0];
        }

        return $ordering;
    }

    /**
     * Return list of menus.
     *
     * @return array
     */
    abstract public function getMenus();

    /**
     * Return list of menus.
     *
     * @return array
     */
    abstract public function getMenuOptions();

    /**
     * Return default menu.
     *
     * @return string|null
     */
    public function getDefaultMenuName()
    {
        return null;
    }

    /**
     * Returns true if the platform implements a Default menu.
     *
     * @return bool
     */
    public function hasDefaultMenu()
    {
        return false;
    }

    /**
     * Return active menu.
     *
     * @return string|null
     */
    public function getActiveMenuName()
    {
        return null;
    }

    /**
     * Returns true if the platform implements an Active menu.
     *
     * @return bool
     */
    public function hasActiveMenu()
    {
        return false;
    }

    /**
     * @param array $params
     * @param Config $menu
     * @return AbstractMenu
     */
    public function instance(array $params = [], Config $menu = null)
    {
        $params += $this->defaults;

        $menus = $this->getMenus();

        if (!$menus) {
            throw new \RuntimeException('Site does not have menus', 404);
        }
        if (empty($params['menu'])) {
            $params['menu'] = $this->getDefaultMenuName();
            if (!$params['menu'] && !empty($params['admin'])) {
                // In admin just select the first menu if there isn't default menu to be selected.
                $params['menu'] = reset($menus);
            };
        } elseif ($params['menu'] === '-active-') {
            $params['menu'] = $this->getActiveMenuName();
        }
        if (!$params['menu']) {
            throw new \RuntimeException('No menu selected', 404);
        }
        if (!\in_array($params['menu'], $menus, true)) {
            throw new \RuntimeException('Menu not found', 404);
        }

        $instance = clone $this;
        $instance->params = $params;

        if ($menu) {
            $menu->set('items', static::prepareMenuItems($menu->get('items'), $menu->get('ordering')));
            $instance->override = true;
            $instance->config = $menu;
        } else {
            $instance->config = null;
        }

        $config = $instance->config();
        $items = isset($config['items']) ? $config['items'] : [];

        // Create menu structure.
        $instance->init($params);

        $instance->pathMap = new Config([]);

        if ($config->get('settings.type') !== 'custom') {
            // Get menu items from the CMS.
            $instance->getList($params, $items);

        } else {
            // Add custom menu items.
            $instance->addCustom($params, $items);
        }

        // Sort menu items.
        $instance->sortAll();

        return $instance;
    }

    /**
     * Get menu configuration.
     *
     * @return Config
     */
    public function config()
    {
        if (!$this->config) {
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $menu = $this->params['menu'];

            $filename = $locator("gantry-config://menu/{$menu}.yaml");
            if ($filename) {
                $file = CompiledYamlFile::instance($filename);
                $content = (array)$file->content();
                $file->free();
            } else {
                $content = [];
            }

            $this->config = new Config($content);
            $this->config->def('settings.title', ucfirst($menu));
        }

        return $this->config;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->params['menu'];
    }

    /**
     * @return Item|null
     */
    public function root()
    {
        return $this->offsetGet($this->root);
    }

    /**
     * @return array
     */
    public function ordering()
    {
        $list = [];
        foreach ($this->items as $item) {
            $groups = $item->groups();
            if (\count($groups) === 1 && empty($groups[0])) {
                continue;
            }

            $id = $item->path ?: '';
            $list[$id] = [];
            foreach ($groups as $col => $children) {
                $list[$id][$col] = [];
                foreach ($children as $child) {
                    $list[$id][$col][] = $child->path ?: '';
                }
            }
        }

        return $list;
    }

    /**
     * @param string $path
     * @return Item|null
     */
    public function get($path)
    {
        if (isset($this->paths[$path])) {
            $id = $this->paths[$path];

            return $this[$id];
        }

        if (isset($this->yaml_paths[$path])) {
            $id = $this->yaml_paths[$path];

            return $this[$id];
        }

        return null;
    }

    /**
     * @param bool $withdefaults
     * @return array
     */
    public function items($withdefaults = true)
    {
        $list = [];
        foreach ($this->items as $key => $item) {
            if ($key !== '') {
                $list[$item->path] = $item->toArray($withdefaults);
            }
        }

        return $list;
    }

    /**
     * @return array
     */
    public function settings()
    {
        return (array) $this->config()->get('settings');
    }

    /**
     * @return object
     */
    public function getBase()
    {
        return $this->offsetGet($this->base);
    }

    /**
     * @return object
     */
    public function getDefault()
    {
        return $this->offsetGet($this->default);
    }

    /**
     * @return object|null
     */
    public function getActive()
    {
        return $this->offsetGet($this->active);
    }

    /**
     * @return string|null
     */
    public function getCacheId()
    {
        return $this->active ?: '-inactive-';
    }

    /**
     * @param object|null $item
     * @return bool
     */
    public function isActive($item)
    {
        $active = $this->getActive();
        if (!$active || !$item) {
            return false;
        }

        return $active->path === $item->path || strpos($active->path, $item->path . '/') === 0;
    }

    /**
     * @param object|null $item
     * @return bool
     */
    public function isCurrent($item)
    {
        $active = $this->getActive();
        if (!$active || !$item) {
            return false;
        }

        return $item->path === $active->path;
    }

    /**
     * @param array $params
     */
    public function init(&$params)
    {
        $this->items = ['' => new Item($this, ['id' => '', 'layout' => 'horizontal'])];
        $this->paths = ['' => ''];
    }

    /**
     * @param Item $item
     * @return $this
     */
    public function add(Item $item)
    {
        if (isset($this->items[$item->id])) {
            // Only add the item once.
            return $this;
        }

        // If parent exists, assign menu item to its parent; otherwise ignore menu item.
        if (isset($this->items[$item->parent_id])) {
            $this->items[$item->parent_id]->addChild($item);
            $this->paths[$item->path] = $item->id;
            if (isset($item->yaml_path)) {
                $this->yaml_paths[$item->yaml_path] = $item->id;

                $this->pathMap->set(preg_replace('|/|u', '/children/', $item->yaml_path) . '/id', $item->id, '/');
            } elseif ($item->path) {
                $this->pathMap->set(preg_replace('|/|u', '/children/', $item->path) . '/id', $item->id, '/');
            }
        }

        $this->items[$item->id] = $item;

        return $this;
    }

    /**
     * Get menu items from the platform.
     *
     * @param int $levels
     * @return array
     */
    abstract protected function getItemsFromPlatform($levels);

    /**
     * Get base menu item.
     *
     * If itemid is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   string  $path
     * @return  string
     */
    abstract protected function calcBase($path);

    /**
     * Get a list of the menu items.
     *
     * @param  array  $params
     * @param  array  $items
     */
    abstract public function getList(array $params, array $items);

    /**
     * Add custom menu items.
     *
     * @param  array  $params
     * @param array $items
     */
    public function addCustom(array $params, array $items)
    {
        $isAjax = !empty($params['POST']);
        $config = $this->config();
        $type = $config->get('settings.type');

        // Add custom menu elements.
        foreach ($items as $route => $item) {
            // If existing menu item does not contain Gantry metadata, update properties from menu YAML.
            $object = isset($this->items[$route]) ? $this->items[$route] : null;
            if ($object) {
                if (empty($object->gantry)) {
                    foreach ($item as $key => $value) {
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
                    $level = \count($tree);
                    $parentTree = $tree;
                    array_pop($parentTree);

                    // Enabled state should equal particle setting.
                    $item['enabled'] = !isset($item['options']['particle']['enabled']) || !empty($item['options']['particle']['enabled']);
                    $item['id'] = $route;
                    $item['parent_id'] = implode('/', $parentTree);
                    $item['alias'] = Gantry::basename($route);
                    $item['level'] = $level;

                    $item = new Item($this, $item);
                    $this->add($item);
                }
            }
        }
    }

    /**
     * @param array $ordering
     * @param string $path
     * @param array $map
     */
    public function sortAll(array $ordering = null, $path = '', $map = null)
    {
        if ($ordering === null) {
            $config = $this->config();
            $ordering = $config['ordering'] ?: [];
        }
        // Ordering in AJAX / YAML file.
        if ($map === null) {
            $map = $this->pathMap ? $this->pathMap->toArray() : [];
        }

        $alias = Gantry::basename($path);
        $key = $map && isset($map[$alias]['id']) ? $map[$alias]['id'] : $path;

        if (!isset($this->items[$key]) || !$this->items[$key]->hasChildren()) {
            return;
        }

        // Ordering in menu item itself.
        $item = $this->items[$key];
        if (!$ordering) {
            $this->setGroupToChildren($item);

            return;
        }

        $order = [];
        $newMap = [];
        if ($this->isAssoc($ordering)) {
            foreach ($ordering as $key => $value) {
                if ($map) {
                    $newMap = isset($map[$key]['children']) ? $map[$key]['children'] : [];
                    $key = isset($map[$key]['id']) ? $map[$key]['id'] : $key;
                    $order[$key] = $value;
                }

                if (\is_array($value)) {
                    $newPath = $path ? $path . '/' . $key : $key;
                    $this->sortAll($value, $newPath, $newMap);
                }
            }

            $item->sortChildren($order ?: $ordering);
        } else {
            foreach ($ordering as $i => $group) {
                foreach ($group as $key => $value) {
                    if ($map) {
                        $newMap = isset($map[$key]['children']) ? $map[$key]['children'] : [];
                        $key = isset($map[$key]['id']) ? $map[$key]['id'] : $key;
                        $order[$i][$key] = $value;
                    }

                    if (\is_array($value)) {
                        $newPath = $path ? $path . '/' . $key : $key;
                        $this->sortAll($value, $newPath, $newMap);
                    }
                }
            }

            $item->groupChildren($order ?: $ordering);
        }
    }

    /**
     * @param Item $item
     */
    protected function setGroupToChildren($item)
    {
        $groups = $item->groups();
        foreach ($groups as $group => $children) {
            foreach ($children as $child) {
                $child->group = $group;
                $this->setGroupToChildren($child);
            }
        }
    }

    /**
     * @param array $array
     * @return bool
     */
    protected function isAssoc(array $array)
    {
        return \array_values($array) !== $array;
    }
}
