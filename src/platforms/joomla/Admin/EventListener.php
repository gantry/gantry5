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

    public function onStylesSave($event)
    {
    }

    public function onSettingsSave($event)
    {
    }

    public function onLayoutSave($event)
    {
    }

    public function onAssignmentsSave($event)
    {
    }
}
