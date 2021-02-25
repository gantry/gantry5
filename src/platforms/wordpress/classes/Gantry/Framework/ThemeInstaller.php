<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Theme\ThemeInstaller as AbstractInstaller;

/**
 * Class ThemeInstaller
 * @package Gantry\Framework
 */
class ThemeInstaller extends AbstractInstaller
{
    /** @var bool */
    public $initialized = true;

    /**
     * @return string
     */
    public function getPath()
    {
        return \get_theme_root() . '/' . $this->name;
    }

    public function createSampleData()
    {
        // TODO: Create menus etc.
    }
}
