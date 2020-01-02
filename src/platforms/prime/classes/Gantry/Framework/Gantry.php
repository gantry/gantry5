<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2020 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\File\CompiledYamlFile;

/**
 * Class Gantry
 * @package Gantry\Framework
 */
class Gantry extends Base\Gantry
{
    /**
     * @return array
     */
    protected function loadGlobal()
    {
        $file = CompiledYamlFile::instance(PRIME_ROOT . '/config/global.yaml');
        $data = (array) $file->setCachePath(PRIME_ROOT . '/cache/global')->content();
        $file->free();

        return $data;
    }
}
