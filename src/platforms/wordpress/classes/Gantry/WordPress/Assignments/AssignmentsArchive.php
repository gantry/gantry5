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

namespace Gantry\WordPress\Assignments;

/**
 * Class AssignmentsArchive
 * @package Gantry\WordPress\Assignments
 */
class AssignmentsArchive extends AssignmentsTaxonomy
{
    /** @var string */
    public $type = 'archive';
    /** @var string */
    public $label = 'Archives: %s';
    /** @var int */
    public $priority = 6;
}
