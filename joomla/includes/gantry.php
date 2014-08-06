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
    // In frontend we want to prevent template from loading.
    if (!class_exists('\Tracy\Debugger'))
    {
        JError::raiseError(500, 'Template cannot be loaded: ' . $e->getMessage());
    };

    // We have Tracy enabled; will display and/or log error with it.
    throw $e;
}
