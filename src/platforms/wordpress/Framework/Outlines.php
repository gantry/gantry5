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

use Gantry\Component\Layout\Layout;
use Gantry\Framework\Base\Outlines as BaseOutlines;

class Outlines extends BaseOutlines
{
    /**
     * Returns list of all menu locations defined in outsets.
     *
     * @return array
     */
    public function menuLocations()
    {
        // TODO: add support for menu locations.
        return [];

        /*
        $list = ['main-navigation' => __('Main Navigation')];
        foreach ($this->items as $name => $title) {
            $index = Layout::index($name);

            $list += isset($index['menus']) ? $index['menus'] : [];
        }

        return $list;
        */
    }
}
