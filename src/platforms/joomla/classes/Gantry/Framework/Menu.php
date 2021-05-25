<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;
use Gantry\Joomla\MenuHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\Router\Route;

/**
 * Class Menu
 * @package Gantry\Framework
 */
class Menu extends AbstractMenu
{
    use GantryTrait;

    /**
     * @var CMSApplication
     */
    protected $application;

    /**
     * @var \Joomla\CMS\Menu\AbstractMenu
     */
    protected $menu;

    public function __construct()
    {
        $this->application = CMSApplication::getInstance('site');

        if (Multilanguage::isEnabled()) {
            /** @var CMSApplication $app */
            $app = Factory::getApplication();
            $language = $app->getLanguage();
            $tag = $language->getTag();
        } else {
            $tag = '*';
        }

        $this->menu = $this->application->getMenu();
        $this->default = $this->menu->getDefault($tag);
        $this->active  = $this->menu->getActive();
    }

    /**
     * @param array $params
     */
    public function init(&$params)
    {
        parent::init($params);

        if (!empty($params['admin'])) {
            $menuType = MenuHelper::getMenuType($params['menu']);

            $config = $this->config();
            $this->id = $menuType->id;
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
            // Works also in Joomla 4
            require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';

            $items = (array)\MenusHelper::getMenuTypes();
        }

        return $items;
    }

    /**
     * @return array
     */
    public function getGroupedItems()
    {
        $groups = [];

        // Works also in Joomla 4
        require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';

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
     * @return bool
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
        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $user = $application->getIdentity();

        if ($user && !$user->guest) {
            return null;
        }

        return $this->active ? $this->active->id : 0;
    }

    /**
     * @param MenuItem $item
     * @return bool
     */
    public function isActive($item)
    {
        $tree = $this->base->tree;

        if (\in_array($item->id, $tree, true)) {
            return true;
        }

        if ($item->type === 'alias') {
            $aliasToId = $item->link_id;

            if (\count($tree) > 0 && $aliasToId === $tree[\count($tree) - 1]) {
                return (bool) $this->params['highlightAlias'];
            }

            if (\in_array($aliasToId, $tree, true)) {
                return (bool) $this->params['highlightParentAlias'];
            }
        }

        return false;
    }

    /**
     * @param MenuItem $item
     * @return bool
     */
    public function isCurrent($item)
    {
        return $item->id == $this->active->id
        || ($item->type === 'alias' && $item->getParams()->get('aliasoptions') == $this->active->id);
    }

    /**
     * Get menu items from the platform.
     *
     * @param array $params
     * @return MenuItem[] List of routes to the pages.
     */
    protected function getItemsFromPlatform($params)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Items are already filtered by access and language, in admin we need to work around that.
        if ($app->isClient('administrator')) {
            $items = $this->getMenuItemsInAdmin($params['menu']);
        } else {
            $attributes = ['menutype'];
            $values = [$params['menu']];

            $items = $this->menu->getItems($attributes, $values);
        }

        return $items;
    }

    /**
     * @param MenuItem[] $menuItems
     * @param array[] $items
     * @return Item[]
     */
    public function createMenuItems($menuItems, $items)
    {
        // Generate lookup indexes using menu item ids and paths.
        $idLookup = [];
        $pathLookup = [];
        foreach ($items as $path => &$item) {
            if (isset($item['yaml_path'])) {
                $path = $item['yaml_path'];
            }

            $path = strtolower(str_replace('/__', '/', trim($path, '_')));
            $item['yaml_path'] = $path;
            $pathLookup[$path] = &$item;

            if (isset($item['id']) && is_numeric($item['id'])) {
                $idLookup[$item['id']] = &$item;
            }
        }
        unset($item);

        $map = [];
        $list = [];
        // Create menu items for the pages.
        foreach ($menuItems as $menuItem) {
            $id = $menuItem->id;
            $path = $menuItem->route;

            // Try to locate Gantry menu item.
            if (isset($idLookup[$id])) {
                // Id found, use it.
                $data = $idLookup[$id];
            } elseif (isset($pathLookup[$path])) {
                // ID not found, use route instead.
                $data = $pathLookup[$path];
            } else {
                // Menu item is not in YAML file.
                $data = ['yaml_path' => $path];
            }
            $map[$data['yaml_path']] = $id;

            $item = $this->createMenuItem($data, $menuItem);
            $list[$item->id] = $item;
        }

        // Create particles which are only inside the menu YAML.
        foreach ($pathLookup as $path => $data) {
            // Ignore everything which is not a module or particle type.
            if (isset($map[$path]) || !isset($data['type']) || !\in_array($data['type'], ['module', 'particle'], true)) {
                continue;
            }

            $level = substr_count($path, '/');
            if ($level) {
                $parentRoute = $level ? dirname($path) : '';

                // If we cannot locate parent, we need to skip the menu item.
                if (!isset($map[$parentRoute])) {
                    continue;
                }

                $parent_id = $map[$parentRoute];
            } else {
                $parent_id = '';
            }

            $data['id'] = $path;
            $data['parent_id'] = $parent_id;

            $item = $this->createMenuItem($data);
            $list[$item->id] = $item;
        }

        return $list;
    }

    /**
     * @param array $data
     * @param MenuItem|null $menuItem
     * @return Item
     */
    protected function createMenuItem($data, $menuItem = null)
    {
        if ($menuItem) {
            // This logic was originally copied from Joomla 3.4 mod_menu/helper.php (joomla-cms/staging, 2014-11-12).
            // We should keep the contents of the function similar to Joomla in order to review it against any changes.

            $id = (int)$menuItem->id;
            $type = $menuItem->type;
            $link = $menuItem->link;
            $params = $menuItem->getParams();

            // Figure out menu link.
            switch ($type) {
                case 'heading':
                case 'separator':
                    // Check if menu item contains a particle.
                    if (!empty($params['gantry-particle'])) {
                        $type = 'particle';
                    }

                    // These types have no link.
                    $link = null;
                    break;

                case 'url':
                    if ((strpos($link, 'index.php?') === 0) && (strpos($link, 'Itemid=') === false)) {
                        // If this is an internal Joomla link, ensure the Itemid is set.
                        $link .= '&Itemid=' . $id;
                    }
                    break;

                case 'alias':
                    // If this is an alias use the item id stored in the parameters to make the link.
                    $link = 'index.php?Itemid=' . $params->get('aliasoptions', 0);

                    // FIXME: Joomla 4: missing multilanguage support
                    break;

                case 'component':
                default:
                    $application = $this->application;
                    $router = $application::getRouter();

                    // FIXME: Joomla 4: do we need anything else?
                    if (version_compare(JVERSION, 4, '<') && $router->getMode() !== JROUTER_MODE_SEF) {
                        $link .= '&Itemid=' . $menuItem->id;
                    } else {
                        $link = 'index.php?Itemid=' . $menuItem->id;

                        if (isset($menuItem->query['format']) && $application->get('sef_suffix')) {
                            $link .= '&format=' . $menuItem->query['format'];
                        }
                    }

                    break;
            }

            // Get default target from Joomla.
            switch ($menuItem->browserNav) {
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

            $level = (int)$menuItem->level;

            $properties = [
                'id' => $id,
                'parent_id' => $level !== 1 ? (int)$menuItem->parent_id : '',
                'path' => $menuItem->route,
                'alias' => $menuItem->alias,
                'type' => $type,
                'link' => $link,
                'enabled' => (bool)$params->get('menu_show', 1),
                'level' => $level,
                'link_title' => $params->get('menu-anchor_title', ''),
                'rel' => $params->get('menu-anchor_rel', ''),
            ];

            // Add Gantry menu item properties from the menu item.
            $paramsEmbedded = false;
            foreach ($params as $param => $value) {
                if (strpos($param, 'gantry-') === 0) {
                    $paramsEmbedded = true;
                    $param = substr($param, 7);

                    // Convert input from Joomla list format.
                    if (is_object($value)) {
                        $value = get_object_vars($value);
                    }
                    if (is_array($value) && in_array($param, ['attributes', 'link_attributes'], true)) {
                        $list = [];
                        foreach ($value as $k => $v) {
                            if (is_array($v) && isset($v->key, $v->value)) {
                                $list[] = [$v->key => $v->value];
                            }
                        }
                        $value = $list;
                    } elseif ($param === 'options') {
                        $value = $value ? json_decode($value, true) : [];
                    }

                    $properties[$param] = $value;
                }
            }

            // Add menu item properties from menu configuration.
            if ($paramsEmbedded === false) {
                $properties = array_replace($properties, $data);
            }

            // And if not available in configuration, default to Joomla.
            $properties += [
                'title' => $menuItem->title,
                // Disabled as the option has different meaning in Joomla than in Gantry, see issue #1656.
                //'anchor_class' => $params->get('menu-anchor_css', ''),
                'image' => $params->get('menu_image', ''),
                'icon_only' => !$params->get('menu_text', 1),
                'target' => $target
            ];

        } else {
            // There is no Joomla menu item.
            $properties = $data;

            $route = $data['id'];
            $level = substr_count($route, '/');

            $properties['enabled'] = !isset($properties['options']['particle']['enabled']) || !empty($properties['options']['particle']['enabled']);
            $properties['alias'] = basename($route);
            $properties['level'] = $level;

            // Deal with special types which do not have link.
            if (in_array($properties['type'], ['module', 'particle', 'separator', 'heading'], true)) {
                $properties['link'] = null;
            }
        }

        $item = new Item($this, $properties);

        $link = $item->link;
        if ($item->type === 'url') {
            // Moved from modules/mod_menu/tmpl/default_url.php, not sure why Joomla had application logic in there.
            // Keep compatibility to Joomla menu module, but we need non-encoded version of the url.
            $link = htmlspecialchars_decode(\JFilterOutput::ampReplace(htmlspecialchars($link, ENT_COMPAT|ENT_SUBSTITUTE, 'UTF-8')));
        }
        if (!$link) {
            $url = false;
        } elseif (strcasecmp(substr($link, 0, 4), 'http') && strpos($link, 'index.php?') !== false) {
            $url = Route::_($link, false, $menuItem->getParams()->get('secure'));
        } else {
            $url = Route::_($link, false);
        }

        $item->url($url);

        return $item;
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
        $menu = $this->application->getMenu();

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
     * @param  array  $params
     * @param  array  $items
     */
    public function getList(array $params, array $items)
    {
        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        // Make sure that the menu item exists.
        if (!$this->base && !$this->application->isClient('administrator')) {
            return;
        }

        $tree    = isset($this->base->tree) ? $this->base->tree : [];
        $start   = $params['startLevel'];
        $max     = $params['maxLevels'];
        $end     = $max ? $start + $max - 1 : 0;

        $menuItems = $this->createMenuItems($this->getItemsFromPlatform($params), $items);
        foreach ($menuItems as $item) {
            $level = $item->level;
            if (($start && $start > $level)
                || ($end && $level > $end)
                || ($start > 1 && !in_array($item->tree[$start - 2], $tree, true))) {
                continue;
            }

            $this->add($item);
        }
    }

    /**
     * This code is taken from Joomla\CMS\Menu\SiteMenu::load()
     *
     * @param string$menutype
     * @return array|null
     */
    private function getMenuItemsInAdmin($menutype)
    {
        $loader = static function () use ($menutype) {
            $db = \JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select('m.id, m.menutype, m.title, m.alias, m.note, m.path AS route, m.link, m.type, m.level, m.language')
                ->select($db->quoteName('m.browserNav') . ', m.access, m.params, m.home, m.img, m.template_style_id, m.component_id, m.parent_id')
                ->select('e.element as component')
                ->from('#__menu AS m')
                ->join('LEFT', '#__extensions AS e ON m.component_id = e.extension_id')
                ->where('m.menutype = ' . $db->quote($menutype))
                ->where('m.parent_id > 0')
                ->where('m.client_id = 0')
                ->order('m.lft');

            // Set the query
            $db->setQuery($query);

            return $db->loadObjectList('id', MenuItem::class);
        };

        try {
            /** @var \JCacheControllerCallback $cache */
            $cache = \JFactory::getCache('com_menus', 'callback');

            $items = $cache->get($loader, [], md5(get_class($this)), false);
        } catch (\JCacheException $e) {
            try {
                $items = $loader();
            } catch (\JDatabaseExceptionExecuting $databaseException) {
                throw new \RuntimeException(\JText::sprintf('JERROR_LOADING_MENUS', $databaseException->getMessage()));
            }
        }
        catch (\JDatabaseExceptionExecuting $e) {
            throw new \RuntimeException(\JText::sprintf('JERROR_LOADING_MENUS', $e->getMessage()));
        }

        foreach ($items as &$item) {
            // Get parent information.
            $parent_tree = [];

            if (isset($items[$item->parent_id])) {
                $parent_tree = $items[$item->parent_id]->tree;
            }

            // Create tree.
            $parent_tree[] = $item->id;
            $item->tree = $parent_tree;

            // Create the query array.
            $url = str_replace('index.php?', '', $item->link);
            $url = str_replace('&amp;', '&', $url);

            parse_str($url, $item->query);
        }

        return array_values($items);
    }
}
