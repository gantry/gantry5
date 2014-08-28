<?php
namespace Gantry\Framework;

use Gantry\Framework\Base\ThemeTrait as GantryThemeTrait;
use Grav\Common\Theme as BaseTheme;
use Grav\Common\Grav;
use Grav\Common\Config as GravConfig;
use RocketTheme\Toolbox\File\YamlFile;

class Theme extends BaseTheme
{
    use GantryThemeTrait;

    public function __construct(Grav $grav, GravConfig $config, $name)
    {
        parent::__construct($grav, $config, $name);

        $baseUrlRelative = $config->get('system.base_url_relative');
        $this->path = THEMES_DIR . $name;
        $this->url = $baseUrlRelative .'/'. USER_PATH . basename(THEMES_DIR) .'/'. $this->name;
    }

    public function render($file, array $context = array()) {}
}
