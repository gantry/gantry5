<?php
defined('_JEXEC') or die;

use Gantry\Framework\Gantry;

try
{
    // Load Gantry Framework.
    $bootstrap = __DIR__ . '/../src/bootstrap.php';

    if (!is_file($bootstrap))
    {
        throw new LogicException( 'Symbolic links missing, please see README.md in your theme!' );
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
    JError::raiseError(500, 'Failed to load template: ' . $e->getMessage());
}
