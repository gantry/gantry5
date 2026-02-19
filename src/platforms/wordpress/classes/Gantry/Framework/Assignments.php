<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Assignments\AbstractAssignments;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class Assignments
 * @package Gantry\Framework
 */
class Assignments extends AbstractAssignments
{
    /** @var string */
    protected $platform = 'WordPress';

    /**
     * Assignments constructor.
     * @param string|null $configuration
     */
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
     * @return array
     */
    public function types()
    {
        $types = [
            'context',
            'menu',
            'language',
            'post',
            'taxonomy',
            'archive'
        ];

        $gantry = Gantry::instance();

        // Use a concrete event with declared property to avoid PHP 8.2+ dynamic property deprecations.
        $event = new class extends Event {
            /** @var array */
            public $types = [];
        };
        $event->types = $types;

        $gantry->fireEvent('assignments.types', $event);

        return \apply_filters('g5_assignments_types', $event->types);
    }

    /**
     * @return array
     */
    public function getPage()
    {
        $list = parent::getPage();

        \do_action('g5_assignments_page', $list);

        return $list;
    }
}
