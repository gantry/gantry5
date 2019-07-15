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

    public function __construct($configuration = null)
    {
        parent::__construct($configuration);

        // Deal with special language assignments.
        $this->specialFilterMethod = function($candidate, $match, $page) {
            if (!empty($candidate['language']) && !empty($page['language'])) {
                // Always drop candidate if language does not match.
                if (empty($match['language'])) {
                    return false;
                }

                unset($candidate['language'], $match['language']);
                $candidate = array_filter($candidate);

                // Special check for the default outline of the language.
                return empty($candidate) || !empty($match);
            }

            return true;
        };
    }

    /**
     * Return list of assignment types.
     *
     * @return array
     */
    public function types()
    {
        $types = ['page', 'language', 'type'];

        return $types;
    }
}
