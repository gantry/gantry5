<?php
namespace Gantry\Framework;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Menu\AbstractMenu;

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
        $this->app = \JApplicationCms::getInstance('site'); //\JFactory::getApplication('site');

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
     */
    public function getMenus()
    {
        static $list;

        if ($list === null) {
            // Get the menu types of menus in the list.
            $db = \JFactory::getDbo();

            // Get the published menu counts.
            $query = $db->getQuery(true)
                ->select('menutype')
                ->from('#__menu_types');

            $db->setQuery($query);

            try
            {
                $list = $db->loadColumn();
            }
            catch (\RuntimeException $e)
            {
                throw $e;
            }
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
        return $this->default->menutype;
    }

    public function isActive($item)
    {
        $path = $this->base->tree;

        if (in_array($item->id, $path)) {
            return true;
        } elseif ($item->type == 'alias') {
            $aliasToId = $item->params->get('aliasoptions');

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
     * @param int $levels
     * @return array    List of routes to the pages.
     */
    protected function getItemsFromPlatform($levels)
    {
        // TODO:
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
     *
     * @return array
     */
    protected function getList(array $params)
    {
        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        // Make sure that the menu item exists.
        if (!$this->base) {
            return [];
        }

        $levels = \JFactory::getUser()->getAuthorisedViewLevels();
        asort($levels);

        $key = 'gantry_menu_items.' . json_encode($params) . json_encode($levels) . '.' . $this->base->id;
        $cache = \JFactory::getCache('mod_menu', '');
        $tree = $cache->get($key);

        if (!$tree) {
            $menu    = $this->app->getMenu();
            $path    = $this->base->tree;
            $start   = $params['startLevel'];
            $end     = $params['endLevel'];
            $showAll = $params['showAllChildren'];

            // Items are already filtered by ViewLevels and user language.
            $menuItems = $menu->getItems('menutype', $params['menu'] ?: $this->default->menutype);

            $all = $tree = [];
            foreach ($menuItems as $item) {
                if (($start && $start > $item->level)
                    || ($end && $item->level > $end)
                    || (!$showAll && $item->level > 1 && !in_array($item->parent_id, $path))
                    || ($start > 1 && !in_array($item->tree[$start - 2], $path))) {
                    continue;
                }


                // TODO: very slow operation for large menus...
                $item->parent = (boolean) $menu->getItems('parent_id', (int) $item->id, true);
                $item->children = [];
                $item->active = false;
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
                        $link = 'index.php?Itemid=' . $item->params->get('aliasoptions');
                        break;

                    default:
                        $app = $this->app;
                        $router = $app::getRouter();

                        if ($router->getMode() == JROUTER_MODE_SEF) {
                            $link = 'index.php?Itemid=' . $item->id;

                            if (isset($item->query['format']) && $app->get('sef_suffix')) {
                                $link .= '&format=' . $item->query['format'];
                            }
                        } else {
                            $link .= '&Itemid=' . $item->id;
                        }
                        break;
                }

                if (!$link) {
                    $item->link = null;
                } elseif (strcasecmp(substr($link, 0, 4), 'http') && (strpos($link, 'index.php?') !== false)) {
                    $item->link = \JRoute::_($link, true, $item->params->get('secure'));
                } else {
                    $item->link = \JRoute::_($link);
                }

                if ($item->type == 'url') {
                    // Moved from modules/mod_menu/tmpl/default_url.php, not sure why Joomla had application logic in there.
                    $item->link = \JFilterOutput::ampReplace(htmlspecialchars($item->link));
                }

                // We prevent the double encoding because for some reason the $item is shared for menu modules and we get double encoding
                // when the cause of that is found the argument should be removed
                $item->title        = htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false);
                $item->anchor_css   = htmlspecialchars($item->params->get('menu-anchor_css', ''), ENT_COMPAT, 'UTF-8', false);
                $item->anchor_title = htmlspecialchars($item->params->get('menu-anchor_title', ''), ENT_COMPAT, 'UTF-8', false);
                $item->menu_image   = $item->params->get('menu_image', '') ?
                    htmlspecialchars($item->params->get('menu_image', ''), ENT_COMPAT, 'UTF-8', false) : '';
                $item->menu_text    = (bool) $item->params->get('menu_text', true);

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

                // Build nested tree structure.
                if (isset($all[$item->parent_id])) {
                    $all[$item->parent_id]->children[] = $item;
                } else {
                    $tree[$item->id] = $item;
                }
                $all[$item->id] = $item;

            }

            $cache->store($tree, $key);
        }

        return $tree;
    }
}
