<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Layout\Layout;
use Gantry\Joomla\CacheHelper;
use Gantry\Joomla\Manifest;
use Gantry\Joomla\StyleHelper;
use Joomla\Registry\Registry;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;
use RocketTheme\Toolbox\File\IniFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class EventListener implements EventSubscriberInterface
{
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

    public function onAdminThemeInit(Event $event)
    {
        \JPluginHelper::importPlugin('gantry5');

        // Trigger the onGantryThemeInit event.
        $dispatcher = \JEventDispatcher::getInstance();
        $dispatcher->trigger('onGantry5AdminInit', ['theme' => $event->theme]);
    }

    public function onGlobalSave(Event $event)
    {
        \JPluginHelper::importPlugin('gantry5');

        // Trigger the onGantryThemeUpdateCss event.
        $dispatcher = \JEventDispatcher::getInstance();
        $dispatcher->trigger('onGantry5SaveConfig', [$event->data]);
    }

    public function onStylesSave(Event $event)
    {
        \JPluginHelper::importPlugin('gantry5');

        // Trigger the onGantryThemeUpdateCss event.
        $dispatcher = \JEventDispatcher::getInstance();
        $dispatcher->trigger('onGantry5UpdateCss', ['theme' => $event->theme]);
    }

    public function onSettingsSave(Event $event)
    {
    }

    public function onLayoutSave(Event $event)
    {
        /** @var Layout $layout */
        $layout = $event->layout;

        if ($layout->name[0] !== '_' && $layout->name !== 'default') {
            $preset = isset($layout->preset['name']) ? $layout->preset['name'] : 'default';

            // Update Joomla template style.
            StyleHelper::update($layout->name, $preset);
        }

        $theme = $event->gantry['theme.name'];

        $positions = $event->gantry['outlines']->positions();
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
        $locator = $event->gantry['locator'];

        $filename = "gantry-theme://language/en-GB/en-GB.tpl_{$theme}_positions.ini";

        $ini = IniFile::instance($locator->findResource($filename, true, true));
        $ini->save($translations);
        $ini->free();
    }

    public function onAssignmentsSave(Event $event)
    {
    }


    public function onMenusSave(Event $event)
    {
        $defaults = [
            'id' => 0,
            'layout' => 'list',
            'target' => '_self',
            'dropdown' => '',
            'icon' => '',
            'image' => '',
            'subtitle' => '',
            'icon_only' => false,
            'visible' => true,
            'group' => 0,
            'columns' => [],
            'link_title' => '',
            'hash' => '',
            'class' => ''
        ];

        $gantry = $event->gantry;
        $menu = $event->menu;

        // Save global menu settings into Joomla.
        /** @var \JTableMenuType $table */
        $menuType = \JTable::getInstance('MenuType');
        if (!$menuType->load(['menutype' => $event->resource])) {
            throw new \RuntimeException("Saving menu failed: Menu type {$event->resource} not found.", 400);
        }
        $options = [
            'title' => $menu['settings.title'],
            'description' => $menu['settings.description']
        ];
        if ($gantry->authorize('menu.edit') && !$menuType->save($options)) {
            throw new \RuntimeException('Saving menu failed: '. $menuType->getError(), 400);
        }

        unset($menu['settings']);

        /** @var \JTableMenu $table */
        $table = \JTable::getInstance('menu');

        foreach ($menu['items'] as $key => $item) {
            $id = !empty($item['id']) ? (int) $item['id'] : 0;
            if ($id && $table->load($item['id'])) {
                $params = new Registry($table->params);

                // Menu item exists in Joomla, let's update it instead.
                unset($item['type'], $item['link']);

                $item['id'] = (int) $id;

                $title = $menu["items.{$key}.title"];
                $browserNav = intval($menu["items.{$key}.target"] === '_blank');

                $options = [
                    // Disabled as the option has different meaning in Joomla than in Gantry, see issue #1656.
                    // 'menu-anchor_css' => $menu["items.{$key}.class"],
                    'menu_image' => $menu["items.{$key}.image"],
                    'menu_text' => intval(!$menu["items.{$key}.icon_only"]),
                    'menu_show' => intval($menu["items.{$key}.enabled"]),
                ];

                $modified = false;

                if ($table->title != $title) {
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
                unset($item['title'], $item['anchor_class'], $item['image'], $item['icon_only'], $item['target']);
                if (version_compare(JVERSION, '3.5.1', '>=')) {
                    unset($item['enabled']);
                }

            }

            // Do not save default values.
            foreach ($defaults as $var => $value) {
                if (isset($item[$var]) && $item[$var] == $value) {
                    unset($item[$var]);
                }
            }

            // Do not save derived values.
            unset($item['path'], $item['alias'], $item['parent_id'], $item['level'], $item['group']);

            // Particles have no link.
            if (isset($item['type']) && $item['type'] === 'particle') {
                unset($item['link']);
            }

            // Because of ordering we need to save all menu items, including those from Joomla which have no data except id.
            $event->menu["items.{$key}"] = $item;
        }

        // Clean the cache.
        CacheHelper::cleanMenu();
    }
}
