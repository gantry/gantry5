<?php
/**
 * Standalone smoke test for the admin file picker controller (no CMS needed).
 *
 * Drives Filepicker::prepareDownload() (the path behind display/download) and
 * resolveRequestPath() for both access levels:
 *   full   = filesystem.manage (Joomla core.manage on com_gantry5/com_templates, WP manage_options)
 *   images = filesystem.images only (Joomla module managers)
 *
 * Usage: php tests/php83/scripts/filepicker-smoke.php
 */
$repo = dirname(__DIR__, 3);
$site = sys_get_temp_dir() . '/g5smoke-' . getmypid();
@mkdir("$site/images/sub", 0777, true); @mkdir("$site/media", 0777, true); @mkdir("$site/outside", 0777, true);
@mkdir("$site/templates/t/images", 0777, true); @mkdir("$site/templates/t/custom", 0777, true);
file_put_contents("$site/configuration.php", "<?php // cfg");
file_put_contents("$site/wp-config.php", "<?php // cfg");
file_put_contents("$site/images/logo.png", base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
file_put_contents("$site/images/notes.txt", "n");
file_put_contents("$site/images/shell.php", "<?php");
file_put_contents("$site/images/.env", "X=1");
file_put_contents("$site/outside/secret.txt", "s");
@symlink("$site/outside", "$site/images/link");
define('GANTRY5_ROOT', $site);
define('JPATH_ROOT', $site);
spl_autoload_register(function ($c) use ($repo) {
    $f = $repo . '/src/classes/' . str_replace('\\', '/', $c) . '.php';
    if (is_file($f)) { require $f; }
});
if (!class_exists('Gantry\Framework\Gantry', false)) {
    require $repo . '/tests/php83/Stubs/FrameworkGantryStub.php';
}
class FakeLocator {
    public $base; public $map;
    function __construct($site) { $this->base = $site; $this->map = ['gantry-media' => ["$site/templates/t/images", "$site/images"], 'gantry-theme' => ["$site/templates/t/custom", "$site/templates/t"], 'gantry-assets' => ["$site/templates/t"]]; }
    function schemeExists($s) { return isset($this->map[$s]); }
    function isStream($u) { $p = explode('://', $u, 2); return count($p) === 2 && isset($this->map[$p[0]]); }
    function findResources($u, $abs = true, $all = false) {
        list($s, $rest) = explode('://', $u, 2); $out = [];
        foreach ($this->map[$s] as $b) { $p = rtrim($b . '/' . $rest, '/'); if (file_exists($p)) $out[] = $p; }
        return $out;
    }
    function findResource($u, $abs = true, $first = false) { $r = $this->findResources($u); return $r ? $r[0] : false; }
}
class SmokePlatform {
    private $actions;
    function __construct(array $actions) { $this->actions = $actions; }
    function authorize($a, $id = null) { return in_array($a, $this->actions, true); }
}
function picker($site, array $actions) {
    $ref = new ReflectionClass('Gantry\Admin\Controller\Json\Filepicker');
    $fp = $ref->newInstanceWithoutConstructor();
    $container = new ArrayObject(['locator' => new FakeLocator($site), 'theme.path' => "$site/templates/t", 'platform' => new SmokePlatform($actions)]);
    Closure::bind(function ($c) { $this->container = $c; }, $fp, get_class($fp))($container);
    return $fp;
}
$levels = ['full' => ['filesystem.manage', 'filesystem.images'], 'images' => ['filesystem.images']];
// [path, expected HTTP code or 'ok' per level]
$cases = [
  ['images/logo.png',                       ['full' => 'ok',  'images' => 'ok']],
  ['gantry-media://logo.png',               ['full' => 'ok',  'images' => 'ok']],
  ['images/notes.txt',                      ['full' => 'ok',  'images' => 403]],
  ['configuration.php',                     ['full' => 403,   'images' => 403]],
  ['wp-config.php',                         ['full' => 403,   'images' => 403]],
  ['/configuration.php',                    ['full' => 403,   'images' => 403]],
  ['images/../configuration.php',           ['full' => 403,   'images' => 403]],
  ['../configuration.php',                  ['full' => 403,   'images' => 403]],
  ['images/%2e%2e/configuration.php',       ['full' => 403,   'images' => 403]],
  ['images/%252e%252e/configuration.php',   ['full' => 403,   'images' => 403]],
  ['images\\..\\configuration.php',         ['full' => 403,   'images' => 403]],
  ['....//configuration.php',               ['full' => 403,   'images' => 403]],
  ['images/../images/logo.png',             ['full' => 403,   'images' => 403]],
  ['images/link/secret.txt',                ['full' => 403,   'images' => 403]],
  ["images/logo.png\0.php",                 ['full' => 403,   'images' => 403]],
  ['images/shell.php',                      ['full' => 403,   'images' => 403]],
  ['images/.env',                           ['full' => 403,   'images' => 403]],
  ['gantry-engine://x',                     ['full' => 403,   'images' => 403]],
  ['gantry-assets://images/../../../configuration.php', ['full' => 403, 'images' => 403]],
];
$fail = 0;
foreach ($levels as $level => $actions) {
    foreach ($cases as $c) {
        $fp = picker($site, $actions);
        $call = Closure::bind(function ($p) { return $this->prepareDownload($p, false); }, $fp, get_class($fp));
        try { $call($c[0]); $got = 'ok'; } catch (RuntimeException $e) { $got = $e->getCode(); }
        $want = $c[1][$level];
        $ok = $got === $want; $fail += $ok ? 0 : 1;
        printf("%-4s %-6s %-55s expect=%-3s got=%s\n", $ok ? 'PASS' : 'FAIL', $level, json_encode($c[0]), $want, $got);
    }
}
// Write-side resolution (upload target) still enforces the write roots.
$fp = picker($site, $levels['full']);
$resolve = Closure::bind(function ($p, $w) { return $this->resolveRequestPath($p, $w); }, $fp, get_class($fp));
foreach ([['images', 'ok'], ['media', 403], ['images/../media', 403]] as $c) {
    try { $resolve($c[0], true); $got = 'ok'; } catch (RuntimeException $e) { $got = $e->getCode(); }
    $ok = $got === $c[1]; $fail += $ok ? 0 : 1;
    printf("%-4s write  %-55s expect=%-3s got=%s\n", $ok ? 'PASS' : 'FAIL', json_encode($c[0]), $c[1], $got);
}
// No-permission user is refused before any path handling.
$fp = picker($site, []);
$call = Closure::bind(function ($p) { return $this->prepareDownload($p, false); }, $fp, get_class($fp));
try { $call('images/logo.png'); $got = 'ok'; } catch (RuntimeException $e) { $got = $e->getCode(); }
$ok = $got === 403; $fail += $ok ? 0 : 1;
printf("%-4s none   %-55s expect=403 got=%s\n", $ok ? 'PASS' : 'FAIL', '"images/logo.png"', $got);
exec('rm -rf ' . escapeshellarg($site));
echo $fail ? "FAILURES: $fail\n" : "ALL PASS\n"; exit($fail ? 1 : 0);
