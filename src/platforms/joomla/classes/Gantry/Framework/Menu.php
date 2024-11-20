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

use Gantry\Component\Config\Config;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;
use Gantry\Joomla\MenuHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\Router\Route;
use Joomla\Component\Menus\Administrator\Helper\MenusHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;

/**
 * Class Menu
 * @package Gantry\Framework
 */
class Menu extends AbstractMenu
{
    use DatabaseAwareTrait;
    use GantryTrait;

    /** @var CMSApplication */
    protected $app;

    /** @var \Joomla\CMS\Menu\AbstractMenu */
    protected $menu;

    /** @var MenuItem */
    protected $default;

    /** @var MenuItem */
    protected $active;

    /** @var MenuItem */
    protected $base;

    /**
     * @param ?DatabaseInterface $db
     */
    public function __construct(?DatabaseInterface $db = null)
    {
        if ($db === null) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        }

        $this->setDatabase($db);

        $this->app = Factory::getApplication();

        if ($this->app->isClient('administrator')) {
            /** @var CMSApplication $app */
            $app = Factory::getContainer()->get(SiteApplication::class);
            $this->menu = $app->getMenu();
        } else {
            $this->menu = $this->app->getMenu();
        }

        $this->active = $this->menu->getActive();

        $tag = Multilanguage::isEnabled() ? $this->app->getLanguage()->getTag() : '*';
        $this->default = $this->menu->getDefault($tag);
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
        $item = $this->items[$offset] ?? null;

        if (!$this->app->isClient('administrator')) {
            return $item && $item->enabled ? $item : null;
        }

        return $item;
    }

    /**
     * @param array $params
     * @param Config $menu
     * @return AbstractMenu
     */
    public function instance(array $params = [], Config $menu = null)
    {
        if ($this->app->isClient('site')) {
            if (Multilanguage::isEnabled() && ($params['menu'] === '-language-')) {
                $tag = $this->app->getLanguage()->getTag();

                $name = \strtolower($params['languageBaseName'] . '-' . $tag);

                if (\array_key_exists($name, $this->getMenuOptions())) {
                    $params['menu'] = $name;
                }
            }
        }

        return parent::instance($params, $menu);
    }

    /**
     * @param array $params
     */
    public function init(&$params): void
    {
        parent::init($params);

        if (!empty($params['admin'])) {
            $menuType = MenuHelper::getMenuType($params['menu']);

            $this->id = $menuType->id;

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
    public function getMenus(): array
    {
        return \array_keys($this->getMenuOptions());
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $list = [];
        foreach ($this->items as $key => $item) {
            if ($key !== '') {
                $list[$item->id] = $item;
            }
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
        static $items;

        if ($items === null) {
            $db    = $this->getDatabase();
            $query = $db->createQuery();

            $query->select($db->quoteName('menutype'))
                ->select($db->quoteName('title'))
                ->from($db->quoteName('#__menu_types'))
                ->where($db->quoteName('client_id') . ' = 0');

            $items = $db->setQuery($query)->loadAssocList('menutype');

            $items = \array_map(static function ($val) {
                return $val['title'];
            }, $items);

            \natsort($items);
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
        static $items;

        if ($items === null) {
            $db    = $this->getDatabase();
            $query = $db->createQuery();

            $query->select($db->quoteName('id'))
                ->from($db->quoteName('#__menu_types', 'a'))
                ->where($db->quoteName('a.client_id') . ' = 0');

            $items = $db->setQuery($query)->loadColumn();
        }

        return $items;
    }

    /**
     * @return array
     */
    public function getGroupedItems()
    {
        $groups = [];

        // Get the menu items.
        $items = MenusHelper::getMenuLinks();

        // Build the groups arrays.
        foreach ($items as $item) {
            // Initialize the group.
            $groups[$item->menutype] = [];

            // Build the options array.
            foreach ($item->links as $link) {
                $groups[$item->menutype][$link->value] = [
                    'spacing' => \str_repeat('&nbsp; ', \max(0, $link->level - 1)),
                    'label'   => $link->text
                ];
            }
        }

        return $groups;
    }

    /**
     * @return object
     */
    public function getDefault()
    {
        return $this->offsetGet($this->default->id);
    }

    /**
     * Return default menu.
     *
     * @return string|null
     */
    public function getDefaultMenuName()
    {
        return $this->default?->menutype;
    }

    /**
     * Returns true if the platform implements a Default menu.
     *
     * @return bool
     */
    public function hasDefaultMenu(): bool
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
        return $this->active?->menutype;
    }

    /**
     * Returns true if the platform implements an Active menu.
     *
     * @return boolean
     */
    public function hasActiveMenu(): bool
    {
        return true;
    }

    /**
     * @return int|null
     */
    public function getCacheId(): int|null
    {
        $user = $this->app->getIdentity();

        if ($user && !$user->guest) {
            return null;
        }

        return $this->active ? $this->active->id : 0;
    }

    /**
     * @param MenuItem $item
     * @return bool
     */
    public function isActive($item): bool
    {
        $tree = $this->base->tree;

        if (\in_array($item->id, $tree, false)) {
            return true;
        }

        if ($item->type === 'alias') {
            $aliasToId = $item->getParams()->get('aliasoptions');

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
    public function isCurrent($item): bool
    {
        return $item->id == $this->active->id
            || ($item->type === 'alias' && $item->getParams()->get('aliasoptions') == $this->active->id);
    }

    /**
     * @param Registry $params
     * @param array $item
     * @return bool
     */
    public static function updateJParams($params, $item): bool
    {
        $modified = false;

        // Convert Gantry params to Registry format.
        $all = Menu::encodeJParams($item);

        // Registry thinks that empty strings do not exist, so work around that.
        $list = $params->toArray();

        foreach ($all as $var => $value) {
            $old = $list[$var] ?? null;

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
    public static function encodeJParams($item = [], $defaultsAsNull = true): array
    {
        // These are stored in Joomla menu item.
        static $ignoreList = ['type', 'link', 'title', 'anchor_class', 'image', 'icon_only', 'target', 'enabled'];

        // Flag menu item to contain gantry data.
        $params = ['gantry' => 1];

        $defaults = Item::$defaults;
        $item     = static::normalizeMenuItem($item + $defaults, $ignoreList, true);

        foreach ($item as $var => $value) {
            if (\is_array($value)) {
                // Joomla has different format for lists than Gantry, convert to Joomla supported version.
                if (\in_array($var, ['attributes', 'link_attributes'], true)) {
                    $list = [];

                    foreach ($value as $k => $v) {
                        if (\is_array($v)) {
                            // Joomla 4: Save lists as {"__field10":{"key":"key","value":"value"}, ...}
                            $list["__field10"] = ['key' => \key($v), 'value' => \current($v)];
                        } else {
                            $list[$k] = $v;
                        }
                    }
                    $value = $list;
                } elseif (\in_array($var, ['options', 'columns', 'columns_count'])) {
                    $value = \json_encode($value);
                }
            }

            // Prefix gantry parameters and save them.
            $var = 'gantry-' . $var;

            $params[$var] = $defaultsAsNull && $value == ($defaults[$var] ?? null) ? null : $value;
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
            if (\strpos($param, 'gantry-') === 0) {
                $param = \substr($param, 7);

                // Convert input from Joomla list format.
                if (\is_object($value)) {
                    $value = \get_object_vars($value);
                }

                if (
                    \is_array($value)
                    && \in_array($param, ['attributes', 'link_attributes'], true)
                ) {
                    $list = [];

                    foreach ($value as $v) {
                        if (\is_object($v) && isset($v->key, $v->value)) {
                            $list[] = [$v->key => $v->value];
                        } elseif (\is_array($v) && isset($v['key'], $v['value'])) {
                            $list[] = [$v['key'] => $v['value']];
                        }
                    }
                    $value = $list;
                } elseif ($param === 'options') {
                    $value = $value ? \json_decode($value, true) : [];
                } elseif (!\is_array($value) && \in_array($param, ['columns', 'columns_count'], true)) {
                    $value = $value ? \json_decode($value, true) : [];
                }

                $properties[$param] = $value;
            }
        }

        return $paramsEmbedded || $properties ? $properties : null;
    }

    /**
     * Get menu items from the platform.
     *
     * @param array $params
     * @return array<string,object|MenuItem> List of routes to the pages.
     */
    protected function getItemsFromPlatform($params): array
    {
        // Items are already filtered by access and language, in admin we need to work around that.
        if ($this->app->isClient('administrator')) {
            $items = $this->getMenuItemsInAdmin($params['menu']);
        } else {
            $items = [];

            foreach ($this->menu->getItems('menutype', $params['menu']) as $item) {
                $items[$item->id] = $item;
            }
        }

        return $items;
    }

    /**
     * @param array<string,object|MenuItem> $menuItems
     * @param array[] $items
     * @return Item[]
     */
    public function createMenuItems($menuItems, $items): array
    {
        // Generate lookup indexes using menu item ids and paths.
        $idLookup   = [];
        $pathLookup = [];

        foreach ($items as $path => &$item) {
            if (isset($item['yaml_path'])) {
                $path = $item['yaml_path'];
            }

            $path = \strtolower(\str_replace('/__', '/', \trim($path, '_')));
            $item['yaml_path'] = $path;
            $pathLookup[$path] = &$item;

            if (isset($item['id']) && \is_numeric($item['id'])) {
                $idLookup[$item['id']] = &$item;
            }
        }

        unset($item);

        $map  = [];
        $list = [];

        if ($this->app->isClient('site')) {
            $inputVars = $this->app->getInput()->getArray();

            $start    = (int) $this->params['startLevel'] ?: 1;
            $lastitem = 0;

            foreach ($menuItems as $i => $menuItem) {
                $menuItem->current = true;

                foreach ($menuItem->query as $key => $value) {
                    if (!isset($inputVars[$key]) || $inputVars[$key] !== $value) {
                        $menuItem->current = false;
                        break;
                    }
                }

                $menuItem->deeper     = false;
                $menuItem->shallower  = false;
                $menuItem->level_diff = 0;

                if (isset($menuItems[$lastitem])) {
                    $menuItems[$lastitem]->deeper     = ($menuItem->level > $menuItems[$lastitem]->level);
                    $menuItems[$lastitem]->shallower  = ($menuItem->level < $menuItems[$lastitem]->level);
                    $menuItems[$lastitem]->level_diff = ($menuItems[$lastitem]->level - $menuItem->level);
                }

                $lastitem = $i;
            }

            if (isset($menuItems[$lastitem])) {
                $menuItems[$lastitem]->deeper     = (($start ?: 1) > $menuItems[$lastitem]->level);
                $menuItems[$lastitem]->shallower  = (($start ?: 1) < $menuItems[$lastitem]->level);
                $menuItems[$lastitem]->level_diff = ($menuItems[$lastitem]->level - ($start ?: 1));
            }
        }

        // Create menu items for the pages.
        foreach ($menuItems as $menuItem) {
            $id   = $menuItem->id;
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
            if (
                isset($map[$path])
                || !isset($data['type'])
                || !\in_array($data['type'], ['module', 'particle'], true)
            ) {
                continue;
            }

            $level = \substr_count($path, '/');

            if ($level) {
                $parentRoute = $level ? \dirname($path) : '';

                // If we cannot locate parent, we need to skip the menu item.
                if (!isset($map[$parentRoute])) {
                    continue;
                }

                $parent_id = $map[$parentRoute];
            } else {
                $parent_id = '';
            }

            $data['id']        = $path;
            $data['parent_id'] = $parent_id;
            $data['path']      = $path;

            $tree         = isset($list[$parent_id]) ? $list[$parent_id]->tree : [];
            $tree[]       = $item->id;
            $data['tree'] = $tree;

            $item = $this->createMenuItem($data);
            $list[$item->id] = $item;
        }

        return $list;
    }


    /**
     * @param array $data
     * @param ?MenuItem|object|null $menuItem
     * @return Item
     */
    protected function createMenuItem($data, $menuItem = null): Item
    {
        if ($menuItem) {
            // This logic was originally copied from Joomla 3.10 mod_menu/helper.php (joomla-cms/staging, 2021-11-09).
            // We should keep the contents of the function similar to Joomla in order to review it against any changes.

            $id      = (int) $menuItem->id;
            $type    = $menuItem->type;
            $link    = $menuItem->link;
            $params  = \method_exists($menuItem, 'getParams') ? $menuItem->getParams() : null;
            $enabled = $params && $params->get('menu_show', 1);

            // Figure out menu link.
            switch ($type) {
                case 'heading':
                case 'separator':
                    // Check if menu item contains a particle.
                    if ($params && !empty($params->get('gantry-particle'))) {
                        $type = 'particle';
                        $options = $params->get('gantry-options');
                        $enabled = $options['particle']['enabled'] ?? true;
                    }

                    // These types have no link.
                    $link = null;
                    break;

                case 'url':
                    if (
                        \strpos($link, 'index.php?') === 0
                        && \strpos($link, 'Itemid=') === false
                    ) {
                        // If this is an internal Joomla link, ensure the Itemid is set.
                        $link .= '&Itemid=' . $id;
                    }
                    break;

                case 'alias':
                    // If this is an alias use the item id stored in the parameters to make the link.
                    $link = 'index.php?Itemid=' . $params->get('aliasoptions', 0);

                    // Get the language of the target menu item when site is multilingual
                    if (Multilanguage::isEnabled()) {
                        $menu = $this->app->getMenu();
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

                    if (isset($menuItem->query['format']) && $this->app->get('sef_suffix')) {
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
                'id'         => $id,
                'parent_id'  => $level !== 1 ? (int) $menuItem->parent_id : '',
                'path'       => $menuItem->route,
                'tree'       => $menuItem->tree,
                'alias'      => $menuItem->alias,
                'type'       => $type,
                'link'       => $link,
                'enabled'    => $enabled,
                'level'      => $level,
                'link_title' => $params ? $params->get('menu-anchor_title', '') : '',
                'rel'        => $params ? $params->get('menu-anchor_rel', '') : '',
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
                $properties = \array_replace($properties, $data);
            }

            // And if not available in configuration, default to Joomla.
            $properties += [
                'title'        => $menuItem->title,
                'anchor_class' => $params ? $params->get('menu-anchor_css', '') : '',
                'image'        => $params ? $params->get('menu_image', '') : '',
                'image_class'  => $params ? $params->get('menu_image_css', '') : '',
                'icon'         => $params ? $params->get('menu_icon_css', '') : '',
                'icon_only'    => $params ? !$params->get('menu_text', 1) : false,
                'target'       => $target,
                'deeper'       => $menuItem->deeper ?? null,
                'shallower'    => $menuItem->shallower ?? null,
                'level_diff'   => $menuItem->level_diff ?? null,
                'current'      => $menuItem->current ?? null,
            ];

            if ($this->app->isClient('site')) {
                $properties += [
                    'deeper'       => $menuItem->deeper ?? null,
                    'shallower'    => $menuItem->shallower ?? null,
                    'level_diff'   => $menuItem->level_diff ?? null,
                    'current'      => $menuItem->current ?? null,
                ];
            }
        } else {
            // There is no Joomla menu item.
            $properties = $data;

            $route = $data['id'];
            $level = \substr_count($route, '/') + 1;

            $properties['enabled'] = !isset($properties['options']['particle']['enabled'])
                || !empty($properties['options']['particle']['enabled']);

            $properties['alias']   = Gantry::basename($route);
            $properties['level']   = $level;

            // Deal with special types which do not have link.
            if (\in_array($properties['type'], ['module', 'particle', 'separator', 'heading'], true)) {
                $properties['link'] = null;
            }
        }

        $item = new Item($this, $properties);

        $link = $item->link;

        if ($item->type === 'url') {
            // Moved from modules/mod_menu/tmpl/default_url.php, not sure why Joomla had application logic in there.
            // Keep compatibility to Joomla menu module, but we need non-encoded version of the url.
            $link = \htmlspecialchars_decode(
                OutputFilter::ampReplace(\htmlspecialchars($link, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8'))
            );
        }
        if (!$link) {
            $url = false;
        } elseif ((\strpos($link, 'index.php?') !== false) && \strcasecmp(\substr($link, 0, 4), 'http')) {
            $url = Route::_($link, false, $params->get('secure'));
        } else {
            $url = Route::_($link, false);
        }

        $item->url($url);

        return $item;
    }

    /**
     * @param array $item
     * @param ?array $ignore
     * @param ?bool $keepDefaults
     * @return array
     */
    protected static function normalizeMenuItem(array $item, array $ignore = [], $keepDefaults = false): array
    {
        static $ignoreList = [
            // Never save derived values.
            'id', 'path', 'route', 'alias', 'parent_id', 'level', 'group', 'current', 'yaml_path', 'yaml_alias'
        ];

        return Item::normalize($item, \array_merge($ignore, $ignoreList), $keepDefaults);
    }

    /**
     * Get base menu item.
     *
     * If itemid is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   ?int  $itemid
     *
     * @return  MenuItem|null
     */
    protected function calcBase($itemid = null): MenuItem|null
    {
        $menu = $this->app->getMenu();

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
        if (!$this->base && !$this->app->isClient('administrator')) {
            return;
        }

        $tree       = $this->base->tree ?? [];
        $start      = (int)$params['startLevel'];
        $max        = (int)$params['maxLevels'];
        $end        = $max ? $start + $max - 1 : 0;
        $this->root = $start > 1 && isset($tree[$start - 2]) ? (int)$tree[$start - 2] : '';

        $menuItems = $this->createMenuItems($this->getItemsFromPlatform($params), $items);

        foreach ($menuItems as $item) {
            $level = $item->level;

            if ($item->id === $this->root) {
                $this->add($item);
                continue;
            }

            if (
                ($start && $start > $level)
                || ($end && $level > $end)
                || ($start > 1 && !\in_array($this->root, $item->tree, false))
            ) {
                continue;
            }

            $this->add($item);
        }
    }

    /**
     * This code is taken from Joomla\CMS\Menu\SiteMenu::load()
     *
     * @param string $menutype
     * @return array
     */
    private function getMenuItemsInAdmin($menutype): array
    {
        $loader = function () use ($menutype): array {
            $db    = $this->getDatabase();
            $query = $db->createQuery();

            $query->select(
                $db->quoteName(
                    [
                        'm.id',
                        'm.menutype',
                        'm.title',
                        'm.alias',
                        'm.note',
                        'm.link',
                        'm.type',
                        'm.level',
                        'm.language',
                        'm.browserNav',
                        'm.access',
                        'm.params',
                        'm.home',
                        'm.img',
                        'm.template_style_id',
                        'm.component_id',
                        'm.parent_id',
                    ]
                )
            )
            ->select(
                $db->quoteName(
                    ['m.path', 'e.element'],
                    ['route', 'component']
                )
            )
                ->from($db->quoteName('#__menu', 'm'))
                ->join(
                    'LEFT',
                    $db->quoteName('#__extensions', 'e'),
                    $db->quoteName('m.component_id') . ' = ' . $db->quoteName('e.extension_id')
                )
                ->where(
                    [
                        $db->quoteName('m.menutype') . ' = :menutype',
                        $db->quoteName('m.published') . ' = 1',
                        $db->quoteName('m.parent_id') . ' > 0',
                        $db->quoteName('m.client_id') . ' = 0',
                    ]
                )
                ->bind(':menutype', $menutype)
                ->order($db->quoteName('m.lft'));

            // Set the query
            $db->setQuery($query);

            $list = [];

            foreach ($db->loadAssocList('id') as $id => $data) {
                $list[$id] = new MenuItem($data);
            }

            return $list;
        };

        try {
            /** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
                ->createCacheController('callback', ['defaultgroup' => 'com_menus']);

            $items = $cache->get($loader, [], md5(\get_class($this) . $menutype), false);
        } catch (CacheExceptionInterface $e) {
            try {
                $this->items = $loader();
            } catch (ExecutionFailureException $databaseException) {
                throw new \RuntimeException(Text::sprintf('JERROR_LOADING_MENUS', $databaseException->getMessage()));
            }
        } catch (ExecutionFailureException $e) {
            throw new \RuntimeException(Text::sprintf('JERROR_LOADING_MENUS', $e->getMessage()));
        }

        foreach ($items as &$item) {
            // Get parent information.
            $parent_tree = [];

            if (isset($items[$item->parent_id])) {
                $item->setParent($items[$item->parent_id]);
                $parent_tree  = $items[$item->parent_id]->tree;
            }

            // Create tree.
            $parent_tree[] = $item->id;
            $item->tree    = $parent_tree;

            // Create the query array.
            $url = \str_replace('index.php?', '', $item->link);
            $url = \str_replace('&amp;', '&', $url);

            \parse_str($url, $item->query);
        }

        return \array_values($items);
    }
}
