<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Layout\Layout;
use Gantry\Framework\Base\Gantry;
use Gantry\Framework\Configurations;
use Gantry\Joomla\CacheHelper;
use Gantry\Joomla\Manifest;
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
            'admin.global.save' => ['onGlobalSave', 0],
            'admin.styles.save' => ['onStylesSave', 0],
            'admin.settings.save' => ['onSettingsSave', 0],
            'admin.layout.save' => ['onLayoutSave', 0],
            'admin.assignments.save' => ['onAssignmentsSave', 0],
            'admin.menus.save' => ['onMenusSave', 0]
        ];
    }

    public function onGlobalSave(Event $event)
    {
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
        $theme = $event->gantry['theme.name'];

        $positions = $event->gantry['configurations']->positions();

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
    }

    public function onAssignmentsSave(Event $event)
    {
    }


    public function onMenusSave(Event $event)
    {
        $table = \JTable::getInstance('menu');
        $menu = $event->menu;

        foreach ($menu['items'] as $key => $item) {
            $id = !empty($item['id']) ? (int) $item['id'] : 0;
            if ($id && $table->load($item['id'])) {
                $params = new Registry($table->params);

                // Menu item exists in Joomla, let's update it instead.
                unset($item['type'], $item['alias'], $item['path'], $item['link'], $item['parent_id']);

                $title = $menu["items.{$key}.title"];

                $options = [
                    'menu-anchor_title' => $menu["items.{$key}.subtitle"],
                    'menu-anchor_css' => $menu["items.{$key}.anchor_class"],
                    'menu_image' => $menu["items.{$key}.image"],
                    'menu_text' => (int) !$menu["items.{$key}.icon_only"],
                    'browserNav' => (int) $menu["items.{$key}.target"] === '_blank'
                ];

                $modified = false;
                foreach ($options as $var => $value) {
                    if ($params->get($var) != $value) {
                        $params->set($var, $value);
                        $modified = true;
                    }
                }

                if ($table->title != $title) {
                    $table->title = $title;
                    $modified = true;
                }

                if ($modified) {
                    $table->params = $params->toArray();
                    if (!$table->check() || !$table->store()) {
                        throw new \RuntimeException($table->getError());
                    }

                    // Clean the cache.
                    CacheHelper::cleanTemplates();
                }

                // Avoid saving values which are also stored in Joomla.
                unset($item['subtitle'], $item['anchor_class'], $item['image'], $item['icon_only'], $item['target']);

                $event->menu->set("items.{$key}", $item);
            }
        }
    }
}
