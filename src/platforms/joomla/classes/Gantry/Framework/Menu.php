<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
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
use Joomla\CMS\Version;
use Joomla\Registry\Registry;

/**
 * Class Menu
 * @package Gantry\Framework
 */
class Menu extends AbstractMenu
{
    use GantryTrait;

    /** @var bool */
    protected $isAdmin;
    /** @var CMSApplication */
    protected $application;
    /** @var \Joomla\CMS\Menu\AbstractMenu */
    protected $menu;

    public function __construct()
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        if ($app->isClient('administrator')) {
            $this->isAdmin = true;
            $this->application = CMSApplication::getInstance('site');
        } else {
            $this->isAdmin = false;
            $this->application = $app;
        }

        if (Multilanguage::isEnabled()) {
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
     * @param string|int $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->offsetGet($offset) !== null;
    }

    /**
     * @param string|int $offset
     * @return Item|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $item = isset($this->items[$offset]) ? $this->items[$offset] : null;
        if (!$this->isAdmin) {
            return $item && $item->enabled ? $item : null;
        }

        return $item;
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
        return array_keys($this->getMenuOptions());
    }

    /**
     * Return list of menus.
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getMenuOptions()
    {
        static $items;

        if ($items === null) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('a.menutype'))
                ->select($db->quoteName('a.title'))
                ->from($db->quoteName('#__menu_types', 'a'))
                ->where($db->quoteName('a.client_id') . ' = 0');

            $db->setQuery($query);

            $items = $db->loadAssocList('menutype');
            $items = array_map(static function($val) { return $val['title']; }, $items);
            natsort($items);
        }

        return $items;
    }

    /**
     * Get menu ids.
     *
     * @return int[]
     */
    public function getMenuIds()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('a.id')
            ->from('#__menu_types AS a');

        $query->where('a.client_id = 0');

        $db->setQuery($query);

        return $db->loadColumn();
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
        if (\in_array($item->id, $tree, false)) {
            return true;
        }

        if ($item->type === 'alias') {
            $aliasToId = $item->link_id;

            if (\count($tree) > 0 && $aliasToId === $tree[\count($tree) - 1]) {
                return (bool) $this->params['highlightAlias'];
            }

            if (\in_array($aliasToId, $tree, false)) {
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
     * @return array<string,object|MenuItem> List of routes to the pages.
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

            $items = [];
            foreach ($this->menu->getItems($attributes, $values) as $item) {
                $items[$item->id] = $item;
            }

            $items = array_replace($this->getMenuItemIds($params['menu']), $items);
        }

        return $items;
    }

    /**
     * @param array<string,object|MenuItem> $menuItems
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
            $data['path'] = $path;

            $tree = isset($list[$parent_id]) ? $list[$parent_id]->tree : [];
            $tree[] = $item->id;
            $data['tree'] = $tree;

            $item = $this->createMenuItem($data);
            $list[$item->id] = $item;
        }

        return $list;
    }

    /**
     * @param Registry $params
     * @param array $item
     * @return bool
     */
    public static function updateJParams($params, $item)
    {
        $modified = false;

        // Convert Gantry params to Registry format.
        $all = Menu::encodeJParams($item);

        // Registry thinks that empty strings do not exist, so work around that.
        $list = $params->toArray();
        foreach ($all as $var => $value) {
            $old = isset($list[$var]) ? $list[$var] : null;
            if ($value !== $old) {
                if (null === $value) {
                    // Remove default value.
                    $params->remove($var);
                } else {
                    // Change value.
                    $params->set($var, $value);
                }

                $modified = true;
            }
        }

        return $modified;
    }

    /**
     * @param array $item
     * @param bool $defaultsAsNull
     * @return int[]
     */
    public static function encodeJParams($item = [], $defaultsAsNull = true)
    {
        // These are stored in Joomla menu item.
        static $ignoreList = ['type', 'link', 'title', 'anchor_class', 'image', 'icon_only', 'target', 'enabled'];

        $version = Version::MAJOR_VERSION;

        // Flag menu item to contain gantry data.
        $params = [
            'gantry' => 1
        ];

        $defaults = Item::$defaults;
        $item = static::normalizeMenuItem($item + $defaults, $ignoreList, true);
        foreach ($item as $var => $value) {
            if (is_array($value)) {
                // Joomla has different format for lists than Gantry, convert to Joomla supported version.
                if (in_array($var, ['attributes', 'link_attributes'], true)) {
                    $i = $version < 4 ? 0 : 10;
                    $list = [];
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            if ($version < 4) {
                                // Joomla 3: Save lists as {"fieldname0":{"key":"key","value":"value"}, ...}
                                $list["{$var}{$i}"] = ['key' => key($v), 'value' => current($v)];
                            } else {
                                // Joomla 4: Save lists as {"__field10":{"key":"key","value":"value"}, ...}
                                $list["__field{$i}"] = ['key' => key($v), 'value' => current($v)];
                            }
                        } else {
                            $list[$k] = $v;
                        }
                        $i++;
                    }
                    $value = $list;
                } elseif (in_array($var, ['options', 'columns', 'columns_count'])) {
                    $value = json_encode($value);
                }
            }

            // Prefix gantry parameters and save them.
            $var = 'gantry-' . $var;

            if ($defaultsAsNull && $value == (isset($defaults[$var]) ? $defaults[$var] : null)) {
                $params[$var] = null;
            } else {
                $params[$var] = $value;
            }
        }

        return $params;
    }

    /**
     * @param iterable $params
     * @return array|null
     */
    public static function decodeJParams($params)
    {
        $properties = [];

        // Add Gantry menu item properties from the menu item.
        $paramsEmbedded = !empty($params['gantry']);
        foreach ($params as $param => $value) {
            if (strpos($param, 'gantry-') === 0) {
                $param = substr($param, 7);

                // Convert input from Joomla list format.
                if (is_object($value)) {
                    $value = get_object_vars($value);
                }
                if (is_array($value) && in_array($param, ['attributes', 'link_attributes'], true)) {
                    $list = [];
                    foreach ($value as $v) {
                        if (is_object($v) && isset($v->key, $v->value)) {
                            $list[] = [$v->key => $v->value];
                        } elseif (is_array($v) && isset($v['key'], $v['value'])) {
                            $list[] = [$v['key'] => $v['value']];
                        }
                    }
                    $value = $list;
                } elseif ($param === 'options') {
                    $value = $value ? json_decode($value, true) : [];
                } elseif (!is_array($value) && in_array($param, ['columns', 'columns_count'], true)) {
                    $value = $value ? json_decode($value, true) : [];
                }

                $properties[$param] = $value;
            }
        }

        return $paramsEmbedded || $properties ? $properties : null;
    }

    /**
     * @param array $data
     * @param MenuItem|object|null $menuItem
     * @return Item
     */
    protected function createMenuItem($data, $menuItem = null)
    {
        if ($menuItem) {
            // This logic was originally copied from Joomla 3.10 mod_menu/helper.php (joomla-cms/staging, 2021-11-09).
            // We should keep the contents of the function similar to Joomla in order to review it against any changes.

            $id = (int)$menuItem->id;
            $type = $menuItem->type;
            $link = $menuItem->link;
            $params = method_exists($menuItem, 'getParams') ? $menuItem->getParams() : null;
            $enabled = $params && $params->get('menu_show', 1);

            // Figure out menu link.
            switch ($type) {
                case 'heading':
                case 'separator':
                    // Check if menu item contains a particle.
                    if ($params && !empty($params->get('gantry-particle'))) {
                        $type = 'particle';
                        $options = $params->get('gantry-options');
                        $enabled = isset($options['particle']['enabled']) ? $options['particle']['enabled'] : true;
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

                    // Get the language of the target menu item when site is multilingual
                    if (Multilanguage::isEnabled()) {
                        $menu = $this->application->getMenu();
                        $newItem = $menu && $params ? $menu->getItem((int) $params->get('aliasoptions')) : null;

                        // Use language code if not set to ALL
                        if ($newItem && $newItem->language && $newItem->language !== '*') {
                            $link .= '&lang=' . $newItem->language;
                        }
                    }
                    break;

                case 'component':
                default:
                    $link = 'index.php?Itemid=' . $menuItem->id;

                    if (isset($menuItem->query['format']) && $this->application->get('sef_suffix')) {
                        $link .= '&format=' . $menuItem->query['format'];
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
                    // Target window: New with navigation.
                    $target = '_blank';
                    break;
                case 2:
                    // Target window: New without navigation.
                    $target = '_nonav';
                    break;
            }

            $level = (int)$menuItem->level;

            $properties = [
                'id' => $id,
                'parent_id' => $level !== 1 ? (int)$menuItem->parent_id : '',
                'path' => $menuItem->route,
                'tree' => $menuItem->tree,
                'alias' => $menuItem->alias,
                'type' => $type,
                'link' => $link,
                'enabled' => $enabled,
                'level' => $level,
                'link_title' => $params ? $params->get('menu-anchor_title', '') : '',
                'rel' => $params ? $params->get('menu-anchor_rel', '') : '',
            ];

            $props = $params ? static::decodeJParams($params) : null;
            if (null !== $props) {
                $paramsEmbedded = true;
                foreach ($props as $param => $value) {
                    $properties[$param] = $value;
                }
            } else {
                $paramsEmbedded = false;
            }

            // Add menu item properties from menu configuration.
            if ($paramsEmbedded === false) {
                $properties = array_replace($properties, $data);
            }

            // And if not available in configuration, default to Joomla.
            $properties += [
                'title' => $menuItem->title,
                'anchor_class' => $params ? $params->get('menu-anchor_css', '') : '',
                'image' => $params ? $params->get('menu_image', '') : '',
                'icon_only' => $params ? !$params->get('menu_text', 1) : false,
                'target' => $target
            ];

        } else {
            // There is no Joomla menu item.
            $properties = $data;

            $route = $data['id'];
            $level = substr_count($route, '/') + 1;

            $properties['enabled'] = !isset($properties['options']['particle']['enabled']) || !empty($properties['options']['particle']['enabled']);
            $properties['alias'] = Gantry::basename($route);
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
        } elseif ((strpos($link, 'index.php?') !== false) && strcasecmp(substr($link, 0, 4), 'http')) {
            $url = Route::_($link, false, $params->get('secure'));
        } else {
            $url = Route::_($link, false);
        }

        $item->url($url);

        return $item;
    }

    /**
     * @param array $item
     * @param array $ignore
     * @param bool $keepDefaults
     * @return array
     */
    protected static function normalizeMenuItem(array $item, array $ignore = [], $keepDefaults = false)
    {
        static $ignoreList = [
            // Never save derived values.
            'id', 'path', 'route', 'alias', 'parent_id', 'level', 'group', 'current', 'yaml_path', 'yaml_alias'
        ];

        return Item::normalize($item, array_merge($ignore, $ignoreList), $keepDefaults);
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
        $base = $itemid && $itemid !== '/' ? $menu->getItem($itemid) : null;

        // Use active menu item or fall back to default menu item.
        return $base ?: $this->active ?: $this->default;
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

        $tree = isset($this->base->tree) ? $this->base->tree : [];
        $start = (int)$params['startLevel'];
        $max = (int)$params['maxLevels'];
        $end = $max ? $start + $max - 1 : 0;
        $this->root = $start > 1 && isset($tree[$start - 2]) ? (int)$tree[$start - 2] : '';

        $menuItems = $this->createMenuItems($this->getItemsFromPlatform($params), $items);
        foreach ($menuItems as $item) {
            $level = $item->level;
            if ($item->id === $this->root) {
                $this->add($item);
                continue;
            }

            if (($start && $start > $level)
                || ($end && $level > $end)
                || ($start > 1 && !in_array($this->root, $item->tree, false))) {
                continue;
            }

            $this->add($item);
        }
    }

    /**
     * @param string $menutype
     * @return array
     */
    private function getMenuItemIds($menutype)
    {
        $db = \JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('m.id, m.alias, m.path AS route, m.level, m.parent_id')
            ->from('#__menu AS m')
            ->where('m.menutype = ' . $db->quote($menutype))
            ->where('m.parent_id > 0')
            ->where('m.client_id = 0')
            ->where('m.published >= 0')
            ->order('m.lft');

        // Set the query
        $db->setQuery($query);

        $items = [];
        foreach ($db->loadAssocList('id') as $id => $data) {
            $data += ['type' => 'separator', 'tree' => [], 'title' => '', 'link' => null, 'browserNav' => null];
            $items[$id] = (object)$data;
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
        }

        return $items;
    }

    /**
     * This code is taken from Joomla\CMS\Menu\SiteMenu::load()
     *
     * @param string $menutype
     * @return array
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
                ->where('m.published >= 0')
                ->order('m.lft');

            // Set the query
            $db->setQuery($query);

            $list = [];
            foreach ($db->loadAssocList('id') as $id => $data) {
                $list[$id] = new MenuItem($data);
            }

            return $list;
        };

        try {
            /** @var \JCacheControllerCallback $cache */
            $cache = \JFactory::getCache('com_menus', 'callback');

            $items = $cache->get($loader, [], md5(get_class($this) . $menutype), false);
        } catch (\JCacheException $e) {
            try {
                $items = $loader();
            } catch (\JDatabaseExceptionExecuting $databaseException) {
                throw new \RuntimeException(\JText::sprintf('JERROR_LOADING_MENUS', $databaseException->getMessage()));
            }
        } catch (\JDatabaseExceptionExecuting $e) {
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
