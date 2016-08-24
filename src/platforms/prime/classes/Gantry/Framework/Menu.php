<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;
use Gantry\Prime\Pages;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Menu extends AbstractMenu
{
    use GantryTrait;

    /**
     * @var Pages
     */
    protected $pages;

    public function __construct()
    {
        $this->default = 'home';
        $this->active  = PAGE_PATH;
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
     * Get menu items from the platform.
     *
     * @param int $levels
     * @return array    List of routes to the pages.
     */
    protected function getItemsFromPlatform($levels)
    {
        if ($this->override) {
            return [];
        }

        // Initialize pages.
        $this->pages = new Pages;

        // Return flat list of routes.
        return array_keys($this->pages->toArray());
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

        $menuItems = array_unique(array_merge($this->getItemsFromPlatform($start <= $end ? $end : -1), array_keys($items)));
        sort($menuItems);

        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        foreach ($menuItems as $name) {
            $parent = dirname($name);
            $level = substr_count($name, '/') + 1;
            if (($start && $start > $level)
                || ($end && $level > $end)
                || ($start > 1 && strpos(dirname($parent), $this->base) !== 0)
                || (!$name || $name[0] == '_' || strpos($name, '_'))
            ) {
                continue;
            }

            $item = new Item($this, $name, isset($items[$name]) && is_array($items[$name]) ? $items[$name] : []);
            $this->add($item);

            // Placeholder page.
            if ($item->type == 'link' && !isset($this->pages[$item->path])) {
                $item->type = 'separator';
            }

            switch ($item->type) {
                case 'hidden':
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
                    if ($item->link == 'home') {
                        // Deal with home page.
                        $item->url('/' . trim(PRIME_URI . '/' . THEME, '/'));
                    } else {
                        $item->url('/' . trim(PRIME_URI . '/' . THEME . '/' . $item->link, '/'));
                    }
            }
        }
    }
}
