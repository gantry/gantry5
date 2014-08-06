<?php

if (!defined('GANTRY5_VERSION')) {

    require_once __DIR__ . '/includes/defines.php';

    $loader = require_once __DIR__ . '/includes/autoload.php';

    // Enable tracy.
    if (DEBUG && !\Tracy\Debugger::isEnabled()) {
        \Tracy\Debugger::enable();
    }
}

return $loader;
