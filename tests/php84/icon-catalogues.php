<?php
/**
 * Real catalogue admission under each supported CMS bootstrap marker.
 * Copyright (c) 2026 Matthew X Murphy, https://MatthewxMurphy.com.
 * SPDX-License-Identifier: MIT
 * Product/support: https://builtnotbegged.com.
 * Run: php tests/php84/icon-catalogues.php. Native HTTP picker checks are separate.
 */
if (PHP_SAPI !== 'cli') { throw new RuntimeException('CLI only.'); }
$checks = 0;
$cases = 0;
$expect = static function ($ok, $label) use (&$checks) {
    if (!$ok) { throw new RuntimeException('Failed: ' . $label); }
    ++$checks;
};
$code = <<<'PHP'
if ($argv[2] !== 'none') { define($argv[2], true); }
$list = include $argv[1];
echo json_encode($list, JSON_THROW_ON_ERROR);
PHP;
foreach (['FontAwesome4.php', 'FontAwesome5.php'] as $name) {
    $file = dirname(__DIR__, 2) . '/src/classes/Gantry/Admin/Controller/Json/Icons/' . $name;
    $baseline = null;
    foreach (['none', 'ABSPATH', '_JEXEC', 'GRAV_ROOT'] as $marker) {
        ++$cases;
        $pipes = [];
        $process = proc_open([PHP_BINARY, '-n', '-d', 'error_reporting=-1', '-r', $code, $file, $marker],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) { throw new RuntimeException('Could not start native PHP.'); }
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $status = proc_close($process);
        $expect($status === 0 && $err === '', $name . ' clean native process for ' . $marker);
        if ($marker === 'none') {
            $expect($out === '', $name . ' direct entry remains refused');
            continue;
        }
        $list = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
        $expect(is_array($list) && count($list) >= 700, $name . ' supplies its catalogue under ' . $marker);
        if ($marker === 'ABSPATH') { $baseline = $list; }
        else { $expect($list === $baseline, $name . ' retains exact catalogue content under ' . $marker); }
    }
}
echo 'PASS ' . $cases . ' catalogue cases / ' . $checks . ' checks on PHP ' . PHP_VERSION . "\n";
