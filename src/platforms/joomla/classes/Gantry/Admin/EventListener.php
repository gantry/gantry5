<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2020 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Layout\Layout;
use Gantry\Component\Menu\Item;
use Gantry\Framework\Gantry;
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
        /** @var Gantry $gantry */
        $gantry = $event->gantry;
        /** @var array $menu */
        $menu = $event->menu;
        /** @var string $resource */
        $resource = $event->resource;

        // Save global menu settings into Joomla.
        $menuType = MenuHelper::getMenuType();
        if (!$menuType->load(['menutype' => $resource])) {
            throw new \RuntimeException("Saving menu failed: Menu type {$resource} not found.", 400);
        }
        $options = [
            'title' => $menu['settings.title'],
            'description' => $menu['settings.description']
        ];
        if ($gantry->authorize('menu.edit') && !$menuType->save($options)) {
            throw new \RuntimeException('Saving menu failed: '. $menuType->getError(), 400);
        }

        unset($menu['settings']);

        $table = MenuHelper::getMenu();

        foreach ($menu['items'] as $key => $item) {
            $id = !empty($item['id']) ? (int) $item['id'] : 0;
            if ($id && $table->load($item['id'])) {
                $params = new Registry($table->params);

                // Menu item exists in Joomla, let's update it instead.
                unset($item['type'], $item['link']);

                $item['id'] = $id;

                $title = $menu["items.{$key}.title"];
                $browserNav = (int)($menu["items.{$key}.target"] === '_blank');

                $options = [
                    // Disabled as the option has different meaning in Joomla than in Gantry, see issue #1656.
                    // 'menu-anchor_css' => $menu["items.{$key}.class"],
                    'menu_image' => $menu["items.{$key}.image"],
                    'menu_text' => (int)(!$menu["items.{$key}.icon_only"]),
                    'menu_show' => (int)$menu["items.{$key}.enabled"],
                ];

                $modified = false;

                if ($table->title !== $title) {
                    $table->title = $title;
                    $modified = true;
                }

                if ($table->browserNav != $browserNav) {
                    $table->browserNav = $browserNav;
                    $modified = true;
                }

                foreach ($options as $var => $value) {
                    if ($params->get($var) !== $value) {
                        $params->set($var, $value);
                        $modified = true;
                    }
                }

                if ($modified && $gantry->authorize('menu.edit')) {
                    $table->params = (string) $params;
                    if (!$table->check() || !$table->store()) {
                        throw new \RuntimeException("Failed to save /{$key}: {$table->getError()}", 400);
                    }
                }

                // Avoid saving values which are also stored in Joomla.
                unset($item['title'], $item['anchor_class'], $item['image'], $item['icon_only'], $item['target'], $item['enabled']);
            }

            $item = $this->normalizeMenuItem($item);

            // Because of ordering we need to save all menu items, including those from Joomla which have no data except id.
            $event->menu["items.{$key}"] = $item;
        }

        // Clean the cache.
        CacheHelper::cleanMenu();
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
            'id', 'path', 'route', 'alias', 'parent_id', 'level', 'group', 'current', 'yaml_path', 'yaml_alias',
            // Also do not save WP variables we do not need.
            'rel', 'attr_title'
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
