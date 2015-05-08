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

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;

class Menu extends AbstractMenu
{
    use GantryTrait;

    /**
     * @var \JApplicationCms
     */
    protected $app;

    /**
     * @var \JMenu
     */
    protected $menu;

    public function __construct()
    {
        $this->app = \JApplicationCms::getInstance('site');

        $lang = \JFactory::getLanguage();
        $tag = \JLanguageMultilang::isEnabled() ? $lang->getTag() : '*';

        $this->menu = $this->app->getMenu();
        $this->default = $this->menu->getDefault($tag);
        $this->active  = $this->menu->getActive();
    }

    /**
     * Return list of menus.
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getMenus()
    {
        static $items;

        if ($items === null) {
            require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';
            $items = \MenusHelper::getMenuTypes();
        }

        return $items;
    }

    /**
     * Return default menu.
     *
     * @return string
     */
    public function getDefaultMenuName()
    {
        return $this->default->menutype;
    }

    public function isActive($item)
    {
        $path = $this->base->tree;

        if (in_array($item->id, $path)) {
            return true;
        } elseif ($item->type == 'alias') {
            $aliasToId = $item->link_id;

            if (count($path) > 0 && $aliasToId == $path[count($path) - 1]) {
                return (bool) $this->params['highlightAlias'];
            } elseif (in_array($aliasToId, $path)) {
                return (bool) $this->params['highlightParentAlias'];
            }
        }

        return false;
    }

    public function isCurrent($item)
    {
        return $item->id == $this->active->id
        || ($item->type == 'alias' && $item->params->get('aliasoptions') == $this->active->id);
    }

    /**
     * Get menu items from the platform.
     *
     * @param array $params
     * @return array    List of routes to the pages.
     */
    protected function getItemsFromPlatform($params)
    {
        // Items are already filtered by ViewLevels and user language.
        return $this->menu->getItems('menutype', $params['menu'] ?: $this->default->menutype);
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
        $menu = $this->app->getMenu();

        // Get base menu item.
        $base = $itemid ? $menu->getItem($itemid) : null;

        if (!$base) {
            // Use active menu item or fall back to default menu item.
            $base = $this->active ?: $this->default;
        }

        // Return base menu item.
        return $base;
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
        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        // Make sure that the menu item exists.
        if (!$this->base) {
            return;
        }

        $levels = \JFactory::getUser()->getAuthorisedViewLevels();
        asort($levels);

        // FIXME: need to create collection class to gather the sibling data, otherwise caching cannot work.
        //$key = 'gantry_menu_items.' . json_encode($params) . '.' . json_encode($levels) . '.' . $this->base->id;
        //$cache = \JFactory::getCache('mod_menu', '');
        //try {
        //    $this->items = $cache->get($key);
        //} catch (\Exception $e) {
        //    $this->items = false;
        //}

        if (1) {
            $path    = $this->base->tree;
            $start   = $params['startLevel'];
            $end     = $params['endLevel'];

            $menuItems = $this->getItemsFromPlatform($params);

            $itemMap = [];
            foreach ($items as $path => &$itemRef) {
                if (isset($itemRef['id']) && is_numeric($itemRef['id'])) {
                    $itemMap[$itemRef['id']] = &$itemRef;
                }
            }

            foreach ($menuItems as $menuItem) {
                if (($start && $start > $menuItem->level)
                    || ($end && $menuItem->level > $end)
                    || ($start > 1 && !in_array($menuItem->tree[$start - 2], $path))) {
                    continue;
                }

                // These params always come from Joomla.
                $itemParams = [
                    'id' => $menuItem->id,
                    'type' => $menuItem->type,
                    'alias' => $menuItem->alias,
                    'path' => $menuItem->route,
                    'link' => $menuItem->link,
                ];

                // Rest of the items will come from saved configuration.
                if (isset($itemMap[$menuItem->id])) {
                    // ID found, use it.
                    $itemParams += $itemMap[$menuItem->id];
                } elseif (isset($items[$menuItem->route])) {
                    // ID not found, try to use route.
                    $itemParams += $items[$menuItem->route];
                }

                // And if not available in configuration, default to Joomla.
                $itemParams += [
                    'title' => $menuItem->title,
                    'subtitle' => $menuItem->params->get('menu-anchor_title', ''),
                    'image' => $menuItem->params->get('menu_image', ''),
                    'icon_only' => !$menuItem->params->get('menu_text', 1)
                ];

                $item = new Item($this, $menuItem->route, $itemParams);
                $this->add($item);

                $link  = $item->link;

                switch ($item->type) {
                    case 'separator':
                    case 'heading':
                        // These types have no link.
                        $link = null;
                        break;

                    case 'url':
                        if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
                            // If this is an internal Joomla link, ensure the Itemid is set.
                            $link = $item->link . '&Itemid=' . $item->id;
                        }
                        break;

                    case 'alias':
                        // If this is an alias use the item id stored in the parameters to make the link.
                        $link = 'index.php?Itemid=' . $menuItem->params->get('aliasoptions', 0);
                        break;

                    default:
                        $app = $this->app;
                        $router = $app::getRouter();

                        if ($router->getMode() == JROUTER_MODE_SEF) {
                            $link = 'index.php?Itemid=' . $item->id;

                            if (isset($menuItem->query['format']) && $app->get('sef_suffix')) {
                                $link .= '&format=' . $menuItem->query['format'];
                            }
                        } else {
                            $link .= '&Itemid=' . $item->id;
                        }
                        break;
                }

                if (!$link) {
                    $item->url(false);
                } elseif (strcasecmp(substr($link, 0, 4), 'http') && (strpos($link, 'index.php?') !== false)) {
                    $item->url(\JRoute::_($link, false, $menuItem->params->get('secure')));
                } else {
                    $item->url(\JRoute::_($link, false));
                }

                if ($item->type == 'url') {
                    // Moved from modules/mod_menu/tmpl/default_url.php, not sure why Joomla had application logic in there.
                    $item->url(\JFilterOutput::ampReplace(htmlspecialchars($item->link)));
                }

                switch ($menuItem->params->get('browserNav', 0))
                {
                    default:
                    case 0:
                        // Target window: Parent.
                        $item->target = '_self';
                        break;
                    case 1:
                    case 2:
                        // Target window: New with navigation.
                        $item->target = '_blank';
                        break;
                }
            }
            // FIXME: need to create collection class to gather the sibling data, otherwise caching cannot work.
            // $cache->store($this->items, $key);
        }
    }
}
