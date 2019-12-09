<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

/**
 * Class Site
 * @package Gantry\Framework
 */
class Site
{
    /** @var string */
    public $url;
    /** @var string */
    public $title;
    /** @var string */
    public $description;

    public function __construct()
    {
        $this->url = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $this->title = 'Title';
        $this->description = 'Description';
    }
}
