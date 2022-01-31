<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Layout\Layout;
use Gantry\Component\Menu\Item;
use Gantry\Framework\Gantry;
use Gantry\Framework\Menu;
use Gantry\Framework\Outlines;
use Gantry\Joomla\CacheHelper;
use Gantry\Joomla\Manifest;
use Gantry\Joomla\MenuHelper;
use Gantry\Joomla\StyleHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;
use RocketTheme\Toolbox\File\IniFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Gantry event listener for admin actions for Joomla.
 * @package Gantry\Admin
 */
class EventListener implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'admin.init.theme'  => ['onAdminThemeInit', 0],
            'admin.global.save' => ['onGlobalSave', 0],
            'admin.styles.save' => ['onStylesSave', 0],
            'admin.settings.save' => ['onSettingsSave', 0],
            'admin.layout.save' => ['onLayoutSave', 0],
            'admin.assignments.save' => ['onAssignmentsSave', 0],
            'admin.menus.save' => ['onMenusSave', 0]
        ];
    }

    /**
     * @param Event $event
     */
    public function onAdminThemeInit(Event $event)
    {
        $this->triggerEvent('onGantry5AdminInit', ['theme' => $event->theme]);
    }

    /**
     * @param Event $event
     */
    public function onGlobalSave(Event $event)
    {
        $this->triggerEvent('onGantry5SaveConfig', [$event->data]);
    }

    /**
     * @param Event $event
     */
    public function onStylesSave(Event $event)
    {
        $this->triggerEvent('onGantry5UpdateCss', ['theme' => $event->theme]);
    }

    /**
     * @param Event $event
     */
    public function onSettingsSave(Event $event)
    {
    }

    /**
     * @param Event $event
     */
    public function onLayoutSave(Event $event)
    {
        /** @var Gantry $gantry */
        $gantry = $event->gantry;

        /** @var Layout $layout */
        $layout = $event->layout;

        $name = $layout->name;
        if ($name[0] !== '_' && $name !== 'default') {
            $preset = isset($layout->preset['name']) ? $layout->preset['name'] : 'default';

            // Update Joomla template style.
            StyleHelper::update($layout->name, $preset);
        }

        $theme = $gantry['theme.name'];

        /** @var Outlines $outlines */
        $outlines = $gantry['outlines'];
        $positions = $outlines->positions();
        $positions['debug'] = 'Debug';

        $manifest = new Manifest($theme);
        $manifest->setPositions(array_keys($positions));
        $manifest->save();

        $translations = [];
        foreach ($positions as $key => $translation) {
            // Encode translation key in Joomla way.
            $key = preg_replace('/[^A-Z0-9_\-]/', '_', strtoupper("TPL_{$theme}_POSITION_{$key}"));
            $translations[$key] = $translation;
        }

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $filename = "gantry-theme://language/en-GB/en-GB.tpl_{$theme}_positions.ini";

        $ini = IniFile::instance($locator->findResource($filename, true, true));
        $ini->save($translations);
        $ini->free();
    }

    /**
     * @param Event $event
     */
    public function onAssignmentsSave(Event $event)
    {
    }

    /**
     * @param Event $event
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function onMenusSave(Event $event)
    {
        /** @var array $menu */
        $menu = $event->menu;
        $event->delete = true;

        // Each menu has ordering from 1..n counting all menu items. Children come right after parent ordering.
        $ordering = Menu::flattenOrdering($menu['ordering']);

        // Prepare menu items data.
        $items = Menu::prepareMenuItems($menu['items'], $menu['ordering'], $ordering);

        // Save global menu settings into Joomla.
        /** @var string $resource */
        $resource = $event->resource;
        $menuType = MenuHelper::getMenuType();
        if (!$menuType->load(['menutype' => $resource])) {
            throw new \RuntimeException("Saving menu failed: Menu type {$resource} not found.", 400);
        }
        $options = [
            'title' => $menu['settings.title'],
            'description' => $menu['settings.description']
        ];

        /** @var Gantry $gantry */
        $gantry = $event->gantry;
        if ($gantry->authorize('menu.edit') && !$menuType->save($options)) {
            throw new \RuntimeException('Saving menu failed: '. $menuType->getError(), 400);
        }

        unset($menu['settings']);

        $stored = $this->getAll($resource);

        // Create database id map to detect moved/deleted menu items.
        $idMap = [];
        foreach ($items as $path => $item) {
            if (!empty($item['id'])) {
                $idMap[$item['id']] = $path;
            }
        }

        $table = MenuHelper::getMenu();

        foreach ($stored as $key => $info) {
            $path = isset($idMap[$key]) ? $idMap[$key] : null;
            if ($info['published'] <= 0) {
                // Ignore trashed menu items.
                continue;
            }

            // Delete removed particles from the menu.
            if (null === $path && $info['type'] === 'heading') {
                $params = json_decode($info['params'], true);
                if (!empty($params['gantry-particle'])) {
                    $table->delete($key, false);
                    unset($stored[$key]);
                }
            }
        }
        $first = reset($stored);

        $menuObject = new Menu();
        foreach ($items as $key => &$item) {
            // Make sure we have all the default values.
            $item = (new Item($menuObject, $item))->toArray(true);
            $type = $item['type'];

            $id = !empty($item['id']) ? (int)$item['id'] : 0;
            if ($id && $table->load($item['id'], true)) {
                // Loaded existing menu item.
                $modified = false;
                $params = new Registry($table->params);

                // Move particles.
                if ($type === 'particle') {
                    $parentKey = dirname($key);
                    $parent = isset($items[$parentKey]) ? $items[$parentKey] : null;
                    $parentId = $parent ? $parent['id'] : null;
                    if ($item['parent_id'] !== $parentId && $item['id'] !== $parentId) {
                        $table->setLocation($parentId ?: $table->getRootId(), 'last-child');
                    }
                }

            } else {
                // Add missing particles into the menu.
                if ($type !== 'particle') {
                    throw new \RuntimeException("Failed to save /{$key}: New menu item is not a particle");
                }
                $modified = true;
                $item['alias'] = strtolower($item['alias'] ?: Gantry::basename($key));
                $parentKey = dirname($key);
                $parentId = !empty($items[$parentKey]['id']) ? (int)$items[$parentKey]['id'] : $table->getRootId();
                $model = isset($stored[$parentId]) ? $stored[$parentId] : $first;

                $table->reset();
                $data = [
                    'id' => 0,
                    'menutype' => $resource,
                    'alias' => $item['alias'],
                    'note' =>  'Menu Particle',
                    'type' => 'heading',
                    'link' => '',
                    'published' => 1,
                    'client_id' => 0,
                    'access' => isset($model['access']) ? (int)$model['access'] : 1,
                    'language' => isset($model['language']) ? $model['language'] : '*'
                ];
                $table->bind($data);
                $table->setLocation($parentId, 'last-child');
                $params = new Registry($table->params);
            }

            $title = $item['title'];
            if ($table->title !== $title) {
                $table->title = $title;
                $modified = true;
            }

            $targets = [
                '_self' => 0,
                '_blank' => 1,
                '_nonav' => 2
            ];
            $target = $item['target'];
            $browserNav = isset($targets[$target]) ? $targets[$target] : 0;
            if ($table->browserNav != $browserNav) {
                $table->browserNav = $browserNav;
                $modified = true;
            }

            // Joomla params.
            $enabled = $type !== 'particle' ? (int)$item['enabled'] : 0; // Hide particles from other menus.
            $options = [
                'menu-anchor_css' => $item['anchor_class'],
                'menu_image' => $item['image'],
                'menu_text' => (int)(!$item['icon_only']),
                'menu_show' => $enabled,
            ];
            foreach ($options as $var => $value) {
                $orig_value = $params->get($var);
                if ($orig_value === null && $value === '') {
                } elseif ($orig_value !== $value) {
                    $params->set($var, $value);
                    $modified = true;
                }
            }

            // Gantry params.
            $modified = Menu::updateJParams($params, $item) | $modified;
            if ($modified && $gantry->authorize('menu.edit')) {
                $table->params = (string) $params;
                if (!$table->check() || !$table->store()) {
                    throw new \RuntimeException("Failed to save /{$key}: {$table->getError()}", 400);
                }
            }

            $key = $table->getKeyName();
            $item['id'] = (int)$table->{$key};

            // We do not need to save anything into a file anymore.
            //$item = $this->normalizeMenuItem($item);
            //$event->menu["items.{$key}"] = $item;
        }
        unset($item);

        // Update database id map to reorder menu items.
        $idMap = [];
        foreach ($items as $path => $item) {
            if (!empty($item['id'])) {
                $idMap[$item['id']] = $path;
            }
        }

        // Finally reorder all menu items.
        $i = isset($first['lft']) ? $first['lft'] : null;
        if ($i) {
            $exists = [];
            $ids = [];
            $lft = [];
            foreach ($idMap as $key => $path) {
                $exists[$key] = true;
                $ids[] = $key;
                $lft[] = $i++;
            }
            foreach ($stored as $key => $info) {
                // Move trashed/missing items into the end of the list.
                if (!isset($exists[$key])) {
                    $ids[] = $key;
                    $lft[] = $i++;
                }
            }

            $table->saveorder($ids, $lft);
        }

        // Clean the cache.
        CacheHelper::cleanMenu();
    }

    /**
     * @param string $menutype
     * @return array
     */
    protected function getAll($menutype)
	{
	    $table = MenuHelper::getMenu();
        $db = $table->getDbo();
        $name = $table->getTableName();
		$key = $table->getKeyName();

		// Get the node and children as a tree.
		$select = 'DISTINCT n.' . $key . ', n.parent_id, n.level, n.lft, n.path, n.type, n.access, n.params, n.language, n.published';
		$query = $db->getQuery(true)
			->select($select)
			->from($name . ' AS n, ' . $name . ' AS p')
			->where('n.lft BETWEEN p.lft AND p.rgt')
			->where('n.menutype = ' . $db->quote($menutype))
            ->where('n.client_id = 0')
			->order('n.lft');

		return $db->setQuery($query)->loadAssocList($key);
	}

    /**
     * @param array $item
     * @param array $ignore
     * @return array
     */
    protected function normalizeMenuItem(array $item, array $ignore = [])
    {
        static $ignoreList = [
            // Never save derived values.
            'id', 'path', 'route', 'alias', 'parent_id', 'level', 'group', 'current', 'yaml_path', 'yaml_alias'
        ];

        return Item::normalize($item, array_merge($ignore, $ignoreList));
    }

    /**
     * @param string $eventName
     * @param array $args
     */
    protected function triggerEvent($eventName, $args = [])
    {
        PluginHelper::importPlugin('gantry5');

        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Trigger the onGantryThemeInit event.
        $app->triggerEvent($eventName, $args);
    }
}
