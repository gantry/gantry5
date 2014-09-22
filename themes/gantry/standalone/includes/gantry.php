<?php
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
    // Oops, something went wrong!

    if (class_exists( '\Tracy\Debugger' ) && \Tracy\Debugger::isEnabled() && !\Tracy\Debugger::$productionMode ) {
        // We have Tracy enabled; will display and/or log error with it.
        throw $e;
    }

    // In frontend we want to prevent template from loading.
    die('Failed to load template: ' . $e->getMessage());
}
