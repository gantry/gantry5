<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

class Theme extends Base\Theme
{
    public function __construct($path, $name = '')
    {
        if (!is_dir($path)) {
            throw new \LogicException('Theme not found!');
        }

        $this->path = $path;
        $this->name = $name ? $name : basename($path);
    }

    public function render($file, array $context = array()) {}
}
