<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Assignments\AbstractAssignments;

class Assignments extends AbstractAssignments
{
    protected $platform = 'Grav';

    /**
     * Return list of assignment types.
     *
     * @return array
     */
    public function types()
    {
        $types = ['page'];

        return $types;
    }
}
