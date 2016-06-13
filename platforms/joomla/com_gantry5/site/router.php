<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die ();

function Gantry5BuildRoute(&$query)
{
    $segments = array();

    unset($query['view']);

    return $segments;
}

function Gantry5ParseRoute($segments)
{
    if ($segments) {
        throw new Exception(JText::_('JERROR_PAGE_NOT_FOUND'), 404);
    }

    return array();
}
