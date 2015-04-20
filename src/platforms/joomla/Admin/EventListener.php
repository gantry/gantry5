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
use Gantry\Joomla\Manifest;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;

class EventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'admin.styles.save' => ['onStylesSave', 0],
            'admin.settings.save' => ['onSettingsSave', 0],
            'admin.layout.save' => ['onLayoutSave', 0],
            'admin.assignments.save' => ['onAssignmentsSave', 0]
        ];
    }

    public function onStylesSave(Event $event)
    {
    }

    public function onSettingsSave(Event $event)
    {
    }

    public function onLayoutSave(Event $event)
    {
        $gantry = Gantry::instance();

        /** @var Configurations $configurations */
        $configurations = $gantry['configurations'];

        $list = [];
        foreach ($configurations as $name => $title) {
            $list += Layout::instance($name)->positions();
        }

        $manifest = new Manifest($gantry['theme.name']);
        $manifest->setPositions(array_keys($list));
        $manifest->save();
    }

    public function onAssignmentsSave(Event $event)
    {
    }
}
