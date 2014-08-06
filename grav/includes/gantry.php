<?php

use Gantry\Framework\Gantry;

// Load Gantry Framework.
$bootstrap = __DIR__ . '/../src/bootstrap.php';
if (!is_file($bootstrap)) {
    throw new LogicException( 'Symbolic links missing, please see README.md in your theme!' );
}

require_once $bootstrap;

// Get Gantry instance and return it.
return Gantry::instance();
