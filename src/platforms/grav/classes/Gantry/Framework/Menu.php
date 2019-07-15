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

namespace Gantry\Framework;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;
use Grav\Common\Grav;
use Grav\Common\Page\Page as GravPage;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Menu extends AbstractMenu
{
    use GantryTrait;

    protected $pages = [];

    public function __construct()
    {
        $grav = Grav::instance();

        /** @var GravPage $page */
        $page = $grav['page'];
        $route = trim($page->rawRoute(), '/');

        $this->default = trim($grav['config']->get('system.home.alias', '/home'), '/');
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
     * @return array
     */
    public function getGroupedItems()
    {
        $groups = array();

        $grav = Grav::instance();

        // Get the menu items.
        $pages = $grav['pages']->all()->nonModular();

        // Initialize the group.
        $groups['mainmenu'] = array();

        // Build the options array.
        /** @var GravPage $page */
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
     * Get menu items from the platform.
     *
     * @param int $levels
     * @return array    List of routes to the pages.
     */
    protected function getItemsFromPlatform($levels)
    {
        $grav = Grav::instance();

        // Initialize pages.
        $pages = $grav['pages']->all()->nonModular();

        // Return flat list of routes.
        $list = [];
        $this->pages = [];
        /** @var GravPage $item */
        foreach ($pages as $item) {
            if (!$item->visible()) {
                continue;
            }

            $level = substr_count($item->rawRoute(), '/');

            if ($levels >= 0 && $level > $levels) {
                continue;
            }

            $name = trim($item->rawRoute(), '/') ?: $this->default;
            $id = preg_replace('|[^a-z0-9]|i', '-', $name);
            $parent_id = \dirname($name) !== '.' ? \dirname($name) : 'root';

            $list[$name] = [
                'id' => $id,
                'type' => $item->isPage() && $item->routable() ? 'link' : 'separator',
                'title' => $item->menu(),
                'link' => $item->url(),
                'parent_id' => $parent_id,
                'layout' => 'list',
                'target' => '_self',
                'dropdown' => '',
                'icon' => '',
                'image' => '',
                'subtitle' => '',
                'icon_only' => false,
                'visible' => $item->visible(),
                'group' => 0,
                'columns' => [],
                'level' => $level,
            ];

            $this->pages[$name] = 1;
        }

        return $list;
    }

    /**
     * Get base menu item.
     *
     * If menu item is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   string  $path
     *
     * @return  string
     */
    protected function calcBase($path)
    {
        if (!$path || !isset($this->pages[$path])) {
            // Use active menu item or fall back to default menu item.
            $path = $this->active ?: $this->default;
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

        $gravItems = $this->getItemsFromPlatform($start <= $end ? $end : -1);
        $menuItems = array_replace_recursive($gravItems, $items);

        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);
        foreach ($menuItems as $name => $data) {
            $parent =  isset($data['parent_id']) ? $data['parent_id'] : 'root';
            $level = isset($data['level']) ? $data['level'] : 1;

            if (($start && $start > $level)
                || ($end && $level > $end)
                || ($start > 1 && strpos($parent, $this->base) !== 0)
            ) {
                continue;
            }

            $item = new Item($this, $name, $data);

            if (!isset($gravItems[$name]) && !\in_array($item->type, ['module', 'particle'], true)) {
                // Ignore removed menu items.
                continue;
            }

            // Placeholder page.
            if ($item->type === 'link' && !isset($this->pages[$item->path])) {
                $item->type = 'separator';
            }

            switch ($item->type) {
                case 'module':
                case 'particle':
                case 'separator':
                case 'heading':
                    // Separator and heading have no link.
                    $item->url(null);
                    break;

                case 'url':
                    $item->url($item->link);
                    break;

                case 'alias':
                default:
                    if ($item->link === '/' . $this->default) {
                        // Deal with home page.
                        $item->url('/');
                    } else {
                        $item->url($item->link);
                    }
            }

            $this->add($item);
        }
    }
}
