<?php

$time_start = microtime(true);

require __DIR__ . "/scssphp/scss.inc.php";


$scss = new scssc();
$scss->setFormatter('scss_formatter_compressed');
$scss->setImportPaths(["../common/scss", "../../gantry/prime/scss", "../../gantry/common/scss"]);
$compiled = $scss->compile('@import "template.scss"');

file_put_contents ('template.css', $compiled);

$time_end = microtime(true);
$time = $time_end - $time_start;

echo "time: " . $time . "s"; 


