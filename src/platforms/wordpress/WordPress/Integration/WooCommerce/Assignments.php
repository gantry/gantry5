<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 * Contains WordPress core code.
 */

namespace Gantry\WordPress\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;

/**
 * Class Assignments
 * @package Gantry\WordPress\Integration\WooCommerce
 */
class AssignmentsWoocommerce implements AssignmentsInterface
{
    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        // TODO
        return [];
    }

    /**
     * List all the rules available.
     *
     * @return array
     */
    public function listRules()
    {
        // TODO
        return [];
    }
}