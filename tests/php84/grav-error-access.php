<?php
/**
 * Disposable Grav dependency-backed error-service access checks.
 * Copyright (c) 2026 Matthew C. Murphy, https://MatthewxMurphy.com.
 * @license MIT; existing Gantry and Grav attribution/licenses remain unchanged.
 *
 * Run on an owned disposable Grav installation, not an installed customer site:
 * GANTRY_GRAV_TEST_SITE=disposable php tests/php84/grav-error-access.php /path/to/grav
 * These checks exercise real Grav user/ACL and Admin context selection in memory;
 * actual password/session authentication requires separate HTTP integration checks.
 */

use Gantry\Framework\Services\ErrorServiceProvider;
use Grav\Common\Grav;
use Grav\Common\User\Interfaces\UserInterface;
use Grav\Plugin\Admin\Admin;
use Pimple\Container;

if (PHP_SAPI !== 'cli' || PHP_VERSION_ID < 80400 || PHP_VERSION_ID >= 80500
    || getenv('GANTRY_GRAV_TEST_SITE') !== 'disposable') {
    throw new RuntimeException('Use actual PHP 8.4 CLI on an explicitly disposable Grav installation.');
}
$root = isset($argv[1]) ? realpath($argv[1]) : false;
if (!$root || !is_file($root . '/vendor/autoload.php') || !is_file($root . '/user/plugins/admin/classes/plugin/Admin.php')) {
    throw new RuntimeException('A disposable Grav installation with the real Admin plugin is required.');
}
chdir($root);
define('GRAV_CLI', true);
define('GRAV_REQUEST_TIME', microtime(true));
$loader = require $root . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/src/platforms/grav/classes/Gantry/Framework/Services/ErrorServiceProvider.php';
$grav = Grav::instance(['loader' => $loader]);
$grav->setup()->initializeCli();
require_once $root . '/user/plugins/admin/classes/plugin/Admin.php';

$hadAdmin = isset($grav['admin']);
$oldAdmin = $hadAdmin ? $grav['admin'] : null;
$hadUser = isset($grav['user']);
$oldUser = $hadUser ? $grav['user'] : null;
$checks = 0;
$expect = static function ($actual, $expected, string $label) use (&$checks) {
    if ($actual !== $expected) {
        throw new RuntimeException('Failed: ' . $label);
    }
    ++$checks;
};
$set = static function (string $key, $value) use ($grav) {
    unset($grav[$key]);
    $grav[$key] = $value;
};
$user = static function (string $name, array $permissions, bool $authenticated, bool $authorized = true) use ($grav): UserInterface {
    // Collection::load creates an unsaved real UserInterface when this synthetic name is absent.
    $actor = $grav['accounts']->load($name);
    if ($actor->exists()) {
        throw new RuntimeException('Synthetic context name is already present; refusing to alter an account.');
    }
    $actor->set('state', 'enabled');
    $actor->set('access', ['admin' => $permissions]);
    $actor->set('authenticated', $authenticated);
    $actor->set('authorized', $authorized);
    return $actor;
};
try {
    $guest = $user('gantry-native-access-guest', ['login' => true, 'plugins' => true], false);
    $editor = $user('gantry-native-access-editor', ['login' => true, 'pages' => true], true);
    $administrator = $user('gantry-native-access-admin', ['login' => true, 'plugins' => true], true);
    $partial = $user('gantry-native-access-partial', ['login' => true, 'plugins' => true], true, false);
    unset($grav['admin'], $grav['user']);
    $expect(ErrorServiceProvider::allowed(), false, 'unavailable user fails closed');
    $set('user', $guest);
    $expect(ErrorServiceProvider::allowed(), false, 'guest permissions alone do not authorize');
    $set('user', $editor);
    $expect(ErrorServiceProvider::allowed(), false, 'frontend pages-only administrator denied');
    $set('user', $partial);
    $expect(ErrorServiceProvider::allowed(), false, 'unfinished second factor denied by real Grav ACL');
    $set('user', $administrator);
    $expect(ErrorServiceProvider::allowed(), true, 'frontend plugin-settings administrator allowed');
    $admin = new Admin($grav, '/admin', 'gantry', '');
    $admin->user = $editor;
    $expect(ErrorServiceProvider::allowed(), false, 'Admin user takes precedence over privileged frontend user');
    $admin->user = $administrator;
    $set('user', $guest);
    $expect(ErrorServiceProvider::allowed(), true, 'authorized Admin user takes precedence over guest frontend user');
    $admin->user = $guest;
    $expect(ErrorServiceProvider::allowed(), false, 'Admin guest denied');
    $denied = new Container();
    (new ErrorServiceProvider())->register($denied);
    $expect(isset($denied['errors']), false, 'register refuses the denied context');
    $admin->user = $administrator;
    $allowed = new Container();
    (new ErrorServiceProvider())->register($allowed);
    $expect($allowed['errors'] === $grav['errors'], true, 'authorized register preserves Grav error object');
    $set('user', $administrator);
    $set('admin', null);
    $expect(ErrorServiceProvider::allowed(), false, 'unavailable Admin does not fall back to a different principal');
} finally {
    unset($grav['admin'], $grav['user']);
    if ($hadAdmin) {
        $grav['admin'] = $oldAdmin;
    }
    if ($hadUser) {
        $grav['user'] = $oldUser;
    }
}
echo json_encode(['ok' => true, 'php' => PHP_VERSION, 'grav' => GRAV_VERSION, 'checks' => $checks], JSON_THROW_ON_ERROR) . PHP_EOL;
