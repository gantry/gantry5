<?php

require_once __DIR__ . '/includes/defines.php';

$errorMessage = 'You are running PHP %s, but Gantry Framework needs at least PHP %s to run.';

// Fail safe version check for PHP <5.4.0, which do not support Bootstrap.
if (version_compare($ver = PHP_VERSION, '5.4.0', '<')) {
    throw new \RuntimeException(sprintf($errorMessage, $ver, GANTRY5_MIN_PHP));
}

$loader = require_once __DIR__ . '/includes/autoload.php';

use \Tracy\Debugger;

// If debug mode is enabled, enable tracy in development mode.
if (DEBUG && !Debugger::isEnabled()) {
    Debugger::enable(Debugger::DEVELOPMENT);
}

// Allow nice debug messages by using tracy.
if (version_compare($ver, GANTRY5_MIN_PHP, '<')) {
    throw new \RuntimeException(sprintf($errorMessage, $ver, GANTRY5_MIN_PHP));
}

return $loader;
