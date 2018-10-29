<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress;

class NavMenuEditWalker extends \Walker_Nav_Menu_Edit
{
    public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        parent::start_el($output, $item, $depth, $args, $id);

        if ('custom' !== $item->type || strpos($item->url, '#gantry-particle-') !== 0) {
            return;
        }

        $output = preg_replace('`field-url`', 'field-url hidden', $output);
    }
}
