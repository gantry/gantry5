<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;
use Grav\Common\Config\Config as GravConfig;
use Grav\Common\Flex\Types\Pages\PageIndex;
use Grav\Common\Grav;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Framework\Flex\Flex;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Menu
 * @package Gantry\Framework
 */
class Menu extends AbstractMenu
{
    use GantryTrait;

    public function __construct()
    {
        $grav = Grav::instance();

        /** @var PageInterface $page */
        $page = $grav['page'];
        $route = trim($page->rawRoute(), '/');

        /** @var GravConfig $config */
        $config = $grav['config'];

        $this->default = trim($config->get('system.home.alias', '/home'), '/');
        $this->active = $route ?: $this->default;
    }

    /**
     * Return list of menus.
     *
     * @return array
     */
    public function getMenus()
    {
        static $list;

        if ($list === null) {
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $finder = new ConfigFileFinder;

            $list = $finder->getFiles($locator->findResources('gantry-config://menu', false));

            // Always have main menu.
            $list += ['mainmenu' => 1];

            $list = array_keys($list);
            sort($list);
        }

        return $list;
    }

    /**
     * Return list of menus.
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getMenuOptions()
    {
        $list = [];
        foreach ($this->getMenus() as $val) {
            $list[$val] = ucwords($val);
        }
        sort($list);

        return $list;
    }

    /**
     * @return array
     */
    public function getGroupedItems()
    {
        $grav = Grav::instance();

        /** @var Flex $flex */
        $flex = $grav['flex'];
        $directory = $flex->getDirectory('pages');
        if (!$directory) {
            throw new \RuntimeException('Flex Pages are required for Gantry to work!');
        }
        /** @var PageIndex $pages */
        $pages = $directory->getCollection();
        $pages = $pages->visible()->nonModular();

        // Initialize the group.
        $groups = ['mainmenu' => []];

        // Build the options array.

        /** @var PageInterface $page */
        foreach ($pages as $page) {
            if (!$page->order()) {
                continue;
            }

            $name = trim($page->rawRoute(), '/') ?: $this->default;
            $path = explode('/', $name);

            $groups['mainmenu'][$name] = [
                'spacing' => str_repeat('&nbsp; ', count($path)-1),
                'label' => $page->title()
            ];
        }

        return $groups;
    }

    /**
     * Return default menu.
     *
     * @return string
     */
    public function getDefaultMenuName()
    {
        return 'mainmenu';
    }

    /**
     * Returns true if the platform implements a Default menu.
     *
     * @return boolean
     */
    public function hasDefaultMenu()
    {
        return true;
    }

    /**
     * Return default menu.
     *
     * @return string
     */
    public function getActiveMenuName()
    {
        return 'mainmenu';
    }

    /**
     * Returns true if the platform implements an Active menu.
     *
     * @return boolean
     */
    public function hasActiveMenu()
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function getCacheId()
    {
        $grav = Grav::instance();

        if (isset($grav['user']) && $grav['user']->authenticated) {
            return null;
        }

        return $this->active ?: '-inactive-';
    }

    /**
     * Get menu items from Grav.
     *
     * @param int $levels
     * @return array    List of routes to the pages.
     */
    protected function getItemsFromPlatform($levels)
    {
        $grav = Grav::instance();

        /** @var Flex $flex */
        $flex = $grav['flex'];
        $directory = $flex->getDirectory('pages');
        if (!$directory) {
            throw new \RuntimeException('Flex Pages are required for Gantry to work!');
        }
        /** @var PageIndex $pages */
        $pages = $directory->getIndex();
        $root = $pages->getRoot();

        $list = [];
        foreach ($root->children() as $next) {
            $list += $this->getItemsFromPlatformRecurse($next, $levels);
        }

        return $list;
    }

    /**
     * @param PageInterface $page
     * @param int $levels
     * @return array
     */
    public function getItemsFromPlatformRecurse(PageInterface $page, $levels)
    {
        if (!$page->visible() || $page->isModule()) {
            return [];
        }

        $name = trim($page->rawRoute(), '/');
        $list = [$name => $page];

        if ($levels) {
            foreach ($page->children() as $next) {
                $list += $this->getItemsFromPlatformRecurse($next, $levels - 1);
            }
        }

        return $list;
    }

    /**
     * @param PageInterface[] $pages
     * @param array[] $items
     * @return Item[]
     */
    public function createMenuItems($pages, $items)
    {
        $this->pathMap = new Config([]);

        $list = [];
        // Create menu items for the pages.
        foreach ($pages as $name => $page) {
            if (isset($items[$name])) {
                $data = $items[$name];
                unset($items[$name]);
            } else {
                $data = [];
            }

            $item = $this->createMenuItem($data, $page);
            $id = $item->id;

            $this->pathMap->set(preg_replace('|/|u', '/children/', $name) . '/id', $id, '/');

            $list[$id] = $item;
        }

        // Create particles which are only inside the menu YAML.
        foreach ($items as $name => $data) {
            // Ignore everything which is not a module or particle type.
            if (!isset($data['type']) || !\in_array($data['type'], ['module', 'particle'], true)) {
                continue;
            }

            $data['id'] = $name;

            $item = $this->createMenuItem($data);
            $id = $item->id;

            $this->pathMap->set(preg_replace('|/|u', '/children/', $name) . '/id', $id, '/');

            $list[$id] = $item;
        }

        return $list;
    }

    /**
     * @param array $data
     * @param PageInterface $page
     * @return Item
     */
    protected function createMenuItem($data, $page = null)
    {
        $route = $page ? $page->rawRoute() : $data['id'];
        $level = substr_count($route, '/');
        $name = trim($route, '/');
        $dirname = \dirname($name);

        // TODO: Grav is missing rel support
        $properties = [
            'id' => $name,
            'parent_id' => $dirname !== '.' ? $dirname : '',
            'alias' => Gantry::basename($name),
            'type' => $page && $page->isPage() && $page->routable() ? 'link' : 'separator',
            'link' => $page ? $page->url() : null,
            'visible' => $page ? $page->visible() : true,
            'level' => $level,
            'title' => $page ? $page->menu() : '',
        ];

        // Add menu item properties from menu configuration.
        if ($data) {
            $properties = array_replace($properties, $data);
        }

        // Add menu item properties from the page header.
        $header = $page ? $page->header() : null;
        if (isset($header->gantry['menu']) && is_array($header->gantry['menu'])) {
            $properties = array_replace($properties, $header->gantry['menu']);
            if (isset($properties['particle'])) {
                $properties['type'] = 'particle';
                $properties['enabled'] = !isset($properties['particle']['enabled']) || !empty($properties['particle']['enabled']);
            }
        }

        // Deal with special types which do not have link.
        if (in_array($properties['type'], ['module', 'particle', 'separator', 'heading'], true)) {
            $properties['link'] = null;
        } elseif ($properties['link'] === "/{$this->default}") {
            // Deal with home page.
            $properties['link'] = $properties['path'] = '/';
        }

        $item = new Item($this, $properties);
        $item->url($item->link);

        return $item;
    }

    /**
     * Get base menu item.
     *
     * If menu item is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   string  $path
     * @param   array   $menuItems
     * @return  string
     */
    protected function calcBase($path, array $menuItems = [])
    {
        $path = trim($path, '/');
        if ($path === '' || !isset($menuItems[$path])) {
            // Use active menu item or fall back to default menu item.
            return $this->active ?: $this->default;
        }

        // Return base menu item.
        return $path;
    }

    /**
     * Get a list of the menu items.
     *
     * Logic has been mostly copied from Joomla 3.4 mod_menu/helper.php (joomla-cms/staging, 2014-11-12).
     * We should keep the contents of the function similar to Joomla in order to review it against any changes.
     *
     * @param  array  $params
     * @param  array  $items
     */
    public function getList(array $params, array $items)
    {
        $start   = $params['startLevel'];
        $max     = $params['maxLevels'];
        $end     = $max ? $start + $max - 1 : 0;

        $pages = $this->getItemsFromPlatform($start <= $end ? $end : -1);
        $menuItems = $this->createMenuItems($pages, $items);

        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base'], $menuItems);
        if ($start > 1) {
            $parts = explode('/', $this->base);
            $this->root = implode('/', array_splice($parts, 0, $start-1));
        }

        foreach ($menuItems as $name => $item) {
            $level = $item->level;

            if ($name === $this->root) {
                $this->add($item);
                continue;
            }

            if (($start && $start > $level)
                || ($end && $level > $end)
                || ($start > 1 && strpos($name, $this->root . '/') !== 0)
            ) {
                continue;
            }

            $this->add($item);
        }

        if ($items) {
            $this->addCustom($params, $items);
        }
    }
}
