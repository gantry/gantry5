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

    public function init(&$params)
    {
        parent::init($params);

        if (!empty($params['admin'])) {
            /** @var \JTableMenuType $table */
            $menuType = \JTable::getInstance('MenuType');
            $menuType->load(['menutype' => $params['menu']]);

            $config = $this->config();
            $config->set('settings.title', $menuType->title);
            $config->set('settings.description', $menuType->description);
        }
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
            $items = (array) \MenusHelper::getMenuTypes();
        }

        return $items;
    }

    public function getGroupedItems()
    {
        $groups = array();

        // Get the menu items.
        $items = \MenusHelper::getMenuLinks();

        // Build the groups arrays.
        foreach ($items as $item) {
            // Initialize the group.
            $groups[$item->menutype] = [];

            // Build the options array.
            foreach ($item->links as $link) {
                $groups[$item->menutype][$link->value] = [
                    'spacing' => str_repeat('&nbsp; ', max(0, $link->level-1)),
                    'label' => $link->text
                ];
            }
        }

        return $groups;
    }

    /**
     * Return default menu.
     *
     * @return string|null
     */
    public function getDefaultMenuName()
    {
        return $this->default ? $this->default->menutype : null;
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
     * Return active menu.
     *
     * @return string|null
     */
    public function getActiveMenuName()
    {
        return $this->active ? $this->active->menutype : null;
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
        if (!\JFactory::getUser()->guest) {
            return null;
        }

        return $this->active ? $this->active->id : 0;
    }

    public function isActive($item)
    {
        $tree = $this->base->tree;

        if (in_array($item->id, $tree)) {
            return true;
        } elseif ($item->type == 'alias') {
            $aliasToId = $item->link_id;

            if (count($tree) > 0 && $aliasToId == $tree[count($tree) - 1]) {
                return (bool) $this->params['highlightAlias'];
            } elseif (in_array($aliasToId, $tree)) {
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
        $attributes = ['menutype'];
        $values = [$params['menu']];

        // Items are already filtered by access and language, in admin we need to work around that.
        if (\JFactory::getApplication()->isAdmin()) {
            $attributes[] = 'access';
            $values[] = null;

            $attributes[] = 'language';
            $values[] = null;
        }

        return $this->menu->getItems($attributes, $values);
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
     * Logic was originally copied from Joomla 3.4 mod_menu/helper.php (joomla-cms/staging, 2014-11-12).
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
            $tree    = $this->base->tree;
            $start   = $params['startLevel'];
            $max     = $params['maxLevels'];
            $end     = $max ? $start + $max - 1 : 0;

            $menuItems = $this->getItemsFromPlatform($params);

            $itemMap = [];
            foreach ($items as $path => &$itemRef) {
                if (isset($itemRef['id']) && is_numeric($itemRef['id'])) {
                    $itemRef['path'] = $path;
                    $itemMap[$itemRef['id']] = &$itemRef;
                }
            }

            foreach ($menuItems as $menuItem) {
                if (($start && $start > $menuItem->level)
                    || ($end && $menuItem->level > $end)
                    || ($start > 1 && !in_array($menuItem->tree[$start - 2], $tree))) {
                    continue;
                }

                // These params always come from Joomla and cannot be overridden.
                $itemParams = [
                    'id' => $menuItem->id,
                    'type' => $menuItem->type,
                    'alias' => $menuItem->alias,
                    'path' => $menuItem->route,
                    'link' => $menuItem->link,
                    'link_title' => $menuItem->params->get('menu-anchor_title', ''),
                    'enabled' => (bool) $menuItem->params->get('menu_show', 1),
                ];

                // Rest of the items will come from saved configuration.
                if (isset($itemMap[$menuItem->id])) {
                    // ID found, use it.
                    $itemParams += $itemMap[$menuItem->id];

                    // Store new path for the menu item into path map.
                    if ($itemParams['path'] !== $itemMap[$menuItem->id]['path']) {
                        if (!$this->pathMap) {
                            $this->pathMap = new Config([]);
                        }
                        $this->pathMap->set(preg_replace('|/|u', '/children/', $itemMap[$menuItem->id]['path']) . '/path', $itemParams['path'], '/');
                    }
                } elseif (isset($items[$menuItem->route])) {
                    // ID not found, try to use route.
                    $itemParams += $items[$menuItem->route];
                }

                // Get default target from Joomla.
                switch ($menuItem->browserNav)
                {
                    default:
                    case 0:
                        // Target window: Parent.
                        $target = '_self';
                        break;
                    case 1:
                    case 2:
                        // Target window: New with navigation.
                        $target = '_blank';
                        break;
                }

                // And if not available in configuration, default to Joomla.
                $itemParams += [
                    'title' => $menuItem->title,
                    'anchor_class' => $menuItem->params->get('menu-anchor_css', ''),
                    'image' => $menuItem->params->get('menu_image', ''),
                    'icon_only' => !$menuItem->params->get('menu_text', 1),
                    'target' => $target
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
                    // Keep compatibility to Joomla menu module, but we need non-encoded version of the url.
                    $item->url(
                        htmlspecialchars_decode(\JFilterOutput::ampReplace(htmlspecialchars($item->link, ENT_COMPAT|ENT_SUBSTITUTE, 'UTF-8')))
                    );
                }
            }

            // FIXME: need to create collection class to gather the sibling data, otherwise caching cannot work.
            // $cache->store($this->items, $key);
        }
    }
}
