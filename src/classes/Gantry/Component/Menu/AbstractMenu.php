<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
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
use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;
use RocketTheme\Toolbox\ArrayTraits\Countable;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\Iterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

abstract class AbstractMenu implements \ArrayAccess, \Iterator, \Countable
{
    use GantryTrait, ArrayAccessWithGetters, Iterator, Export, Countable;

    protected $default;
    protected $base;
    protected $active;
    protected $params;
    protected $override = false;
    protected $config;

    /**
     * @var array|Item[]
     */
    protected $items;

    /**
     * @var Config|null
     */
    protected $pathMap;

    protected $defaults = [
        'menu' => '',
        'base' => '/',
        'startLevel' => 1,
        'maxLevels' => 0,
        'showAllChildren' => true,
        'highlightAlias' => true,
        'highlightParentAlias' => true
    ];

    abstract public function __construct();

    /**
     * Return list of menus.
     *
     * @return array
     */
    abstract public function getMenus();


    /**
     * Return default menu.
     *
     * @return string
     */
    public function getDefaultMenuName()
    {
        return null;
    }

    /**
     * Returns true if the platform implements a Default menu.
     *
     * @return boolean
     */
    public function hasDefaultMenu()
    {
        return false;
    }

    /**
     * Return active menu.
     *
     * @return string
     */
    public function getActiveMenuName()
    {
        return null;
    }

    /**
     * Returns true if the platform implements an Active menu.
     *
     * @return boolean
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
        $params = $params + $this->defaults;

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
        } elseif ($params['menu'] == '-active-') {
            $params['menu'] = $this->getActiveMenuName();
        }
        if (!$params['menu']) {
            throw new \RuntimeException('No menu selected', 404);
        }
        if (!in_array($params['menu'], $menus)) {
            throw new \RuntimeException('Menu not found', 404);
        }

        $instance = clone $this;
        $instance->params = $params;

        if ($menu) {
            $instance->override = true;
            $instance->config = $menu;
        } else {
            $instance->config = null;
        }

        $config = $instance->config();
        $items = isset($config['items']) ? $config['items'] : [];

        // Create menu structure.
        $instance->init($params);

        // Get menu items from the system (if not specified otherwise).
        if ($config->get('settings.type') !== 'custom') {
            $instance->getList($params, $items);
        }

        // Add custom menu items.
        $instance->addCustom($params, $items);

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

            $file = CompiledYamlFile::instance($locator("gantry-config://menu/{$menu}.yaml"));
            $this->config = new Config($file->content());
            $this->config->def('settings.title', ucfirst($menu));
            $file->free();
        }

        return $this->config;
    }

    public function name()
    {
        return $this->params['menu'];
    }

    public function root()
    {
        return $this->offsetGet('');
    }

    public function ordering()
    {
        $list = [];
        foreach ($this->items as $name => $item) {
            $groups = $item->groups();
            if (count($groups) == 1 && empty($groups[0])) {
                continue;
            }

            $list[$name] = [];
            foreach ($groups as $col => $children) {
                $list[$name][$col] = [];
                foreach ($children as $child) {
                    $list[$name][$col][] = $child->path;
                }
            }
        }

        return $list;
    }

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
     * @return object
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

    public function isActive($item)
    {
        $active = $this->getActive();

        if ($active && $item && ($active->path === $item->path || strpos($active->path, $item->path . '/') === 0)) {
            return true;
        }

        return false;
    }

    public function isCurrent($item)
    {
        $active = $this->getActive();

        return $item && $active && $item->path === $active->path;
    }

    public function init(&$params)
    {
        $this->items = ['' => new Item($this, '', ['layout' => 'horizontal'])];
    }

    public function add(Item $item)
    {
        $this->items[$item->path] = $item;

        // If parent exists, assign menu item to its parent; otherwise ignore menu item.
        if (isset($this->items[$item->parent_id])) {
            $this->items[$item->parent_id]->addChild($item);
        } elseif (!$this->items['']->count()) {
            $this->items[$item->parent_id] = $this->items[''];
            $this->items[$item->parent_id]->addChild($item);
        }

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
     *
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
        $start   = $params['startLevel'];
        $max     = $params['maxLevels'];
        $end     = $max ? $start + $max - 1 : 0;

        $config = $this->config();
        $type = $config->get('settings.type');

        // Add custom menu elements.
        foreach ($items as $route => $item) {
            if ($type !== 'custom' && (!isset($item['type']) || $item['type'] !== 'particle')) {
                continue;
            }

            $tree = explode('/', $route);
            $parentTree = $tree;
            array_pop($parentTree);

            // Enabled state should equal particle setting.
            $item['enabled'] = !isset($item['options']['particle']['enabled']) || !empty($item['options']['particle']['enabled']);
            $item['level'] = $level = count($tree);
            $item['parent_id'] = implode('/', $parentTree);
            if (($start && $start > $level)
                || ($end && $level > $end)
                // TODO: Improve. In the mean time Item::add() handles this part.
                // || ($start > 1 && !in_array($tree[$start - 2], $tree))
            ) {
                continue;
            }
            $item = new Item($this, $route, $item);
            $this->add($item);
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
            $ordering = $config['ordering'] ? $config['ordering'] : [];
        }

        if (!isset($this->items[$path]) || !$this->items[$path]->hasChildren()) {
            return;
        }

        if ($map === null) {
            $map = $this->pathMap ? $this->pathMap->toArray() : [];
        }

        $order = [];
        $newMap = [];
        $item = $this->items[$path];
        if ($this->isAssoc($ordering)) {
            foreach ($ordering as $key => $value) {
                if ($map) {
                    $newMap = isset($map[$key]['children']) ? $map[$key]['children'] : [];
                    $key = isset($map[$key]['path']) ? basename($map[$key]['path']) : $key;
                    $order[$key] = $value;
                }

                if (is_array($value)) {
                    $this->sortAll($value, $path ? $path . '/' . $key : $key, $newMap);
                }
            }

            $item->sortChildren($order ?: $ordering);
        } else {
            foreach ($ordering as $i => $group) {
                foreach ($group as $key => $value) {
                    if ($map) {
                        $newMap = isset($map[$key]['children']) ? $map[$key]['children'] : [];
                        $key = isset($map[$key]['path']) ? basename($map[$key]['path']) : $key;
                        $order[$i][$key] = $value;
                    }

                    if (is_array($value)) {
                        $this->sortAll($value, $path ? $path . '/' . $key : $key, $newMap);
                    }
                }
            }

            $item->groupChildren($order ?: $ordering);
        }

    }

    protected function isAssoc(array $array)
    {
        return (array_values($array) !== $array);
    }
}
