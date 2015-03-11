<?php
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
     */
    protected function getList(array $params)
    {
        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        // Make sure that the menu item exists.
        if (!$this->base) {
            return;
        }

        //$levels = \JFactory::getUser()->getAuthorisedViewLevels();
        //asort($levels);

        //$key = 'gantry_menu_items.' . json_encode($params) . '.' . json_encode($levels) . '.' . $this->base->id;
        //$cache = \JFactory::getCache('mod_menu', '');
        //$this->items = $cache->get($key);

        if (!$this->items) {
            $config = $this->config();
            $items   = isset($config['items']) ? $config['items'] : [];

            $path    = $this->base->tree;
            $start   = $params['startLevel'];
            $end     = $params['endLevel'];
            $showAll = $params['showAllChildren'];

            $menuItems = $this->getItemsFromPlatform($params);

            $this->items = ['' => new Item($this, '', ['layout' => 'horizontal'])];
            foreach ($menuItems as $menuItem) {
                if (($start && $start > $menuItem->level)
                    || ($end && $menuItem->level > $end)
                    || (!$showAll && $menuItem->level > 1 && !in_array($menuItem->parent_id, $path))
                    || ($start > 1 && !in_array($menuItem->tree[$start - 2], $path))) {
                    continue;
                }

                $itemParams = isset($items[$menuItem->route]) ? $items[$menuItem->route] : [];
                $itemParams += [
                    'id' => $menuItem->id,
                    'type' => $menuItem->type,
                    'path' => $menuItem->route,
                    'alias' => $menuItem->alias,
                    'title' => $menuItem->title,
                    'link' => $menuItem->link,
                    'link_id' => $menuItem->params->get('aliasoptions', 0),
                    'browserNav' => $menuItem->params->get('browserNav', 0),
                    'menu_text' => $menuItem->params->get('menu_text', 1)
                ];

                $item = new Item($this, $menuItem->route, $itemParams);
                $this->add($item);

                $link  = $item->link;

                switch ($item->type) {
                    case 'separator':
                    case 'heading':
                        // Separator and heading has no link.
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
                        $link = 'index.php?Itemid=' . $item->link_id;
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
                    $item->url(\JRoute::_($link, true, $menuItem->params->get('secure')));
                } else {
                    $item->url(\JRoute::_($link));
                }

                if ($item->type == 'url') {
                    // Moved from modules/mod_menu/tmpl/default_url.php, not sure why Joomla had application logic in there.
                    $item->url(\JFilterOutput::ampReplace(htmlspecialchars($item->link)));
                }

                $item->anchor_css   = $menuItem->params->get('menu-anchor_css', '');
                $item->anchor_title = $menuItem->params->get('menu-anchor_title', '');
                $item->menu_image   = $menuItem->params->get('menu_image', '');

                switch ($item->browserNav)
                {
                    default:
                    case 0:
                        // Target window: Parent.
                        $item->anchor_attributes = '';
                        break;
                    case 1:
                        // Target window: New with navigation.
                        $item->anchor_attributes = ' target="_blank"';
                        break;
                    case 2:
                        // Target window: New without navigation.
                        $item->anchor_attributes = ' onclick="window.open(this.href,\'targetWindow\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes' . ($params['window_open'] ? ',' . $params['window_open'] : '') . '\');return false;"';
                        break;
                }
            }

            $this->sortAll();

            //$cache->store($this->items, $key);
        }
    }
}
