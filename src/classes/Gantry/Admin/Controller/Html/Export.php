<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use Gantry\Joomla\Module\ModuleFinder;
use Symfony\Component\Yaml\Yaml;

class Export extends HtmlController
{
    public function index()
    {
        // Experimental module exporter...
        $list = [];
        if (class_exists('Gantry\Joomla\Module\ModuleFinder')) {
            $finder = new ModuleFinder;
            $modules = $finder->particle()->find();

            foreach ($modules as $module) {
                $list[] = $module->toArray();
            }
        }
        die(Yaml::dump($list));
    }
}
