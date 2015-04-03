<?php
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
        throw new Exception('Page not found', 404);
    }

    return array();
}
