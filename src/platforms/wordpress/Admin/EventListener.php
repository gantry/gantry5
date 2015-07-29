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

use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;

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
        $option = (array) \get_option('gantry5_plugin');
        $option['production'] = intval((bool) $event->data['production']);
        \update_option('gantry5_plugin', $option);
    }

    public function onStylesSave(Event $event)
    {
    }

    public function onSettingsSave(Event $event)
    {
    }

    public function onLayoutSave(Event $event)
    {
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
            'columns' => []
        ];

        $menu = $event->menu;

        // TODO: Modify WP menus.
        foreach ($menu['items'] as $key => $item) {
            // Do not save default values.
            foreach ($defaults as $var => $value) {
                if (isset($item[$var]) && $item[$var] == $value) {
                    unset($item[$var]);
                }
            }

            // Do not save WP variables we do not use.
            unset($item['xfn'], $item['attr_title']);

            // Do not save derived values.
            unset($item['path'], $item['alias'], $item['parent_id'], $item['level'], $item['group'], $item['current']);

            // Particles have no link.
            if (isset($item['type']) && $item['type'] === 'particle') {
                unset($item['link']);
            }

            $event->menu["items.{$key}"] = $item;
        }
    }
}
