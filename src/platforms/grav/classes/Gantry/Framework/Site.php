<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Grav\Common\Config\Config;
use Grav\Common\Grav;
use Grav\Common\Uri;

/**
 * Class Site
 * @package Gantry\Framework
 */
class Site
{
    /** @var string */
    public $theme;
    /** @var string */
    public $url;
    /** @var string */
    public $title;
    /** @var string */
    public $description;

    public function __construct()
    {
        $grav = Grav::instance();

        /** @var Config $config */
        $config = $grav['config'];

        /** @var Uri $uri */
        $uri = $grav['uri'];

        $this->theme = $config->get('system.theme');
        $this->url = $uri->rootUrl();
        $this->title = $config->get('site.title');
        $this->description = $config->get('site.description');
    }
}
