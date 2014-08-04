<?php
namespace Gantry;

use Gantry\Base\Config;

class Gantry extends Base\Gantry
{
    public function initialize(Theme\Theme $theme)
    {
        $this->site =  new Site\Site;

        $this->theme = $theme;

        $path = $theme->path;
        $this->config = Config::instance(JPATH_CACHE . '/gantry5/config.php', $path);
    }
}
