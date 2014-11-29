<?php
defined('PRIME_ROOT') or die;

use Gantry\Framework\Gantry;

try
{
    // Attempt to locate Gantry Framework if it hasn't already been loaded.
    if (!class_exists('Gantry'))
    {
        $gantryPaths = array(
            PRIME_ROOT . '/src/bootstrap.php',          // Look if Gantry has been included to the admin.
        );

        foreach ($gantryPaths as $gantryPath)
        {
            if ($gantryPath && is_file($gantryPath))
            {
                $gantryBootstrap = $gantryPath;
            }
        }

        if (!isset($gantryBootstrap))
        {
            throw new LogicException('Gantry Framework not found!');
        }

        require_once $gantryBootstrap;

        unset($gantryPaths, $gantryPath, $gantryBootstrap);
    }

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
    echo '500 Failed to load admin: ' . $e->getMessage();
    die();
}
