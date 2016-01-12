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
