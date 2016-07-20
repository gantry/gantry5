<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Joomla\Module\ModuleFinder;

class Exporter
{
    public function positions()
    {
        $finder = new ModuleFinder();
        $modules = $finder->particle()->find();

        return $modules->export();
    }
}
