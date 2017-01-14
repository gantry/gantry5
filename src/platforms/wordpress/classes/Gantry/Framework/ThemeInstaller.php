<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Theme\ThemeInstaller as AbstractInstaller;

class ThemeInstaller extends AbstractInstaller
{
    public $initialized = true;

    protected $extension;
    protected $manifest;

    public function getPath()
    {
        return get_theme_root() . '/' . $this->name;
    }

    public function createSampleData()
    {
        // TODO: Create menus etc.
    }
}
