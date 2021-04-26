#!/usr/bin/env php
<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

setlocale(LC_ALL, ['en_GB.utf8', 'en_GB']);
date_default_timezone_set('America/Denver');
chdir(__DIR__ . '/build');

$hasVersion = false;
foreach ($argv as $arg) {
    if (strpos($arg, '-Dxml.version=') === 0) {
        $hasVersion = true;
        break;
    }
}
if (false === $hasVersion) {
    $file = fopen(dirname(__DIR__) . '/CHANGELOG.md','rb');
    if (preg_match('/^# (\d\.\d+.\d+(-[a-z0-9.]+)?)\s*$/i', fgets($file), $matches) !== 1) {
        echo 'First line of CHANGELOG.md has wrong Gantry version format, aborting';
        return 1;
    }
    $version = $matches[1];
    $argv[] = '-Dxml.version=' . $version;
}

require_once __DIR__ . '/build/vendor/bin/phing';
