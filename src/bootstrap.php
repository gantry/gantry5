<?php
namespace Gantry;

use \Tracy\Debugger;

function bootstrap()
{
    $errorMessage = 'You are running PHP %s, but Gantry Framework needs at least PHP %s to run.';

    // Fail safe version check for PHP <5.4.0, which do not support Bootstrap.
    if (version_compare($phpVersion = PHP_VERSION, '5.4.0', '<')) {
        throw new \RuntimeException(sprintf($errorMessage, $phpVersion, GANTRY5_MIN_PHP));
    }

    require_once __DIR__ . '/includes/defines.php';
    require_once __DIR__ . '/includes/autoload.php';

    $loader = autoload();

    // If debug mode is enabled, enable tracy in development mode.
    if (GANTRY_DEBUG && !Debugger::isEnabled()) {
        Debugger::enable(Debugger::DEVELOPMENT);
    }

    // Allow nice debug messages by using tracy.
    if (version_compare($phpVersion, GANTRY5_MIN_PHP, '<')) {
        throw new \RuntimeException(sprintf($errorMessage, $phpVersion, GANTRY5_MIN_PHP));
    }

    return $loader;
}

return bootstrap();
