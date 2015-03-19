<?php
defined('IN_PHPBB') or die;

use Gantry\Framework\Gantry;

try
{
    $bootstrap = __DIR__ . '/../src/bootstrap.php';
    if (!$bootstrap)
    {
        throw new LogicException('Gantry Framework not found!');
    }

    require_once $bootstrap;

    // Get Gantry instance and return it.
    return Gantry::instance();
}
catch (Exception $e)
{
    die('Failed to load template: ' . $e->getMessage());
}
