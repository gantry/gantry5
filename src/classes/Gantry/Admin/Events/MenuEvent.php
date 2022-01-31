<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Events;

use Gantry\Component\Config\Config;
use Gantry\Component\Controller\RestfulControllerInterface;
use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class MenuEvent
 * @package Gantry\Admin\Events
 */
class MenuEvent extends Event
{
    /** @var Gantry */
    public $gantry;
    /** @var Theme */
    public $theme;
    /** @var RestfulControllerInterface */
    public $controller;
    /** @var string */
    public $resource;
    /** @var Config */
    public $menu;
    /** @var bool */
    public $save = true;
    /** @var bool */
    public $delete = false;
    /** @var array|null */
    public $debug;
}
