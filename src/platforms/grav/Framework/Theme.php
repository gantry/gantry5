<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Base\ThemeTrait as GantryThemeTrait;
use Grav\Common\Theme as BaseTheme;
use Grav\Common\Grav;
use Grav\Common\Config\Config as GravConfig;
use RocketTheme\Toolbox\File\YamlFile;

class Theme extends BaseTheme
{
    use GantryThemeTrait;

    /**
     * @var ThemeDetails
     */
    protected $details;

    public function __construct(Grav $grav, GravConfig $config, $name)
    {
        parent::__construct($grav, $config, $name);

        // $this->init();

        $baseUrlRelative = $grav['base_url_relative'];
        $this->name = $name;
        $this->path = THEMES_DIR . $name;
        $this->url = $baseUrlRelative .'/'. USER_PATH . basename(THEMES_DIR) .'/'. $this->name;
    }

    public function render($file, array $context = array()) {}
}
