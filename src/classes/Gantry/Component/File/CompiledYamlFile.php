<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\File;

use RocketTheme\Toolbox\File\YamlFile;

class CompiledYamlFile extends YamlFile
{
    use CompiledFile;

    static public $defaultCachePath;
    static public $defaultCaching = true;

    protected function __construct()
    {
        parent::__construct();

        $this->caching(static::$defaultCaching);

        if (static::$defaultCachePath) {
            $this->setCachePath(static::$defaultCachePath);
        }
    }
}
