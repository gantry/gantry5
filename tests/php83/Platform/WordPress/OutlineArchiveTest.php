<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework)
 * @currentDeveloper  Tiger12, LLC
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Tests\PHP83\Platform\WordPress;

use Gantry\WordPress\OutlineArchive;
use PHPUnit\Framework\TestCase;

/**
 * Class OutlineArchiveTest
 * @package Gantry\Tests\PHP83\Platform\WordPress
 */
class OutlineArchiveTest extends TestCase
{
    /** @var string */
    protected $tmpRoot;

    /** @var callable */
    protected $parse;

    /** @var callable */
    protected $dump;

    /** @var OutlineArchive */
    protected $archive;

    /** @var string */
    protected $siteDir;

    /** @var string */
    protected $customConfig;

    /** @var string */
    protected $themeConfig;

    /** @var string */
    protected $mediaDir;

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir() . '/gantry-outline-test-' . uniqid('', true);
        @mkdir($this->tmpRoot, 0755, true);

        if (class_exists('Symfony\Component\Yaml\Yaml')) {
            $this->parse = static function ($s) {
                return \Symfony\Component\Yaml\Yaml::parse($s);
            };
            $this->dump = static function ($d) {
                return \Symfony\Component\Yaml\Yaml::dump($d, 10, 2);
            };
        } else {
            $this->parse = static function ($s) {
                $data = json_decode($s, true);
                if ($data === null && trim((string) $s) !== 'null' && strtolower(trim((string) $s)) !== 'null') {
                    // Distinguish invalid JSON from encoded null.
                    if (trim((string) $s) !== '' && json_last_error() !== JSON_ERROR_NONE) {
                        throw new \RuntimeException('Invalid YAML/JSON');
                    }
                    if ($data === null && trim((string) $s) !== 'null') {
                        throw new \RuntimeException('Invalid YAML/JSON');
                    }
                }

                return $data;
            };
            $this->dump = static function ($d) {
                return json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            };
        }

        $this->archive = new OutlineArchive($this->parse, $this->dump);
        $this->buildSiteA();
    }

    protected function tearDown(): void
    {
        if ($this->tmpRoot && is_dir($this->tmpRoot)) {
            $this->deleteTree($this->tmpRoot);
        }
    }

    /**
     * @return void
     */
    public function testCollectOutlines()
    {
        $outlines = $this->archive->collectOutlines([$this->customConfig, $this->themeConfig]);

        $this->assertSame(['default', 'home'], array_keys($outlines));
        $this->assertSame('Default', $outlines['default']['title']);
        $this->assertSame('Home', $outlines['home']['title']);
        $this->assertSame('default', $outlines['default']['preset']);
        $this->assertSame('home', $outlines['home']['preset']);

        $cfg = $outlines['default']['config'];
        $this->assertArrayHasKey('index', $cfg);
        $this->assertArrayHasKey('layout', $cfg);
        $this->assertArrayHasKey('styles', $cfg);
        $this->assertArrayHasKey('page/head', $cfg);
        $this->assertArrayHasKey('particles/logo', $cfg);
        $this->assertArrayHasKey('content/blog', $cfg);

        // Custom styles override theme styles.
        $this->assertSame('custom-styles', $cfg['styles']['value']);
        $this->assertSame('gantry-media://logo.png', $cfg['particles/logo']['image']);
    }

    /**
     * @return void
     */
    public function testRoundTripIntoCleanState()
    {
        $dirs = [$this->customConfig, $this->themeConfig];
        $outlines = $this->archive->collectOutlines($dirs);
        $menus = $this->archive->collectMenus($dirs);

        $mediaDir = $this->mediaDir;
        $exists = static function ($uri) use ($mediaDir) {
            if (strpos($uri, 'gantry-media://') === 0) {
                $path = substr($uri, \strlen('gantry-media://'));

                return is_file($mediaDir . '/' . $path);
            }

            return false;
        };

        $files = $this->archive->collectFileRefs($outlines, $menus, $exists);

        $widgets = [
            'sidebar-1' => [
                ['type' => 'gantry.particle', 'particle' => ['id' => 'logo']],
                ['type' => 'wordpress.text', 'wordpress' => ['title' => 'Hello']],
            ],
            'sidebar-2' => [
                ['type' => 'wordpress.search', 'wordpress' => []],
            ],
        ];
        $titles = [
            'sidebar-1' => 'Main Sidebar',
            'sidebar-2' => 'Footer',
        ];
        $positions = $this->archive->positionsForExport($widgets, $titles);

        $exported = [
            'export' => [
                'gantry' => ['version' => '5.test', 'format' => 1],
                'platform' => ['name' => 'wordpress', 'version' => '6.0'],
                'theme' => ['name' => 'test-theme'],
            ],
            'outlines' => $outlines,
            'positions' => $positions,
            'menus' => $menus,
            'content' => [],
            'categories' => [],
            'files' => $files,
        ];

        $zipPath = $this->tmpRoot . '/export.zip';
        $this->buildZip($exported, $zipPath, static function ($uri) use ($mediaDir) {
            if (strpos($uri, 'gantry-media://') === 0) {
                return $mediaDir . '/' . substr($uri, \strlen('gantry-media://'));
            }

            return false;
        });

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);
        $this->archive->assertSafeZip($zip);

        $extractDir = $this->tmpRoot . '/extracted';
        @mkdir($extractDir, 0755, true);
        $zip->extractTo($extractDir);
        $zip->close();

        $target = $this->tmpRoot . '/target-config';
        @mkdir($target . '/old', 0755, true);
        file_put_contents($target . '/old/index.yaml', \call_user_func($this->dump, ['stale' => true]));
        @mkdir($target . '/menu', 0755, true);
        file_put_contents($target . '/menu/keep.yaml', \call_user_func($this->dump, ['keep' => true]));

        $imported = $this->archive->importOutlines($extractDir . '/outlines', $target, true);
        $this->assertSame(['default', 'home'], $imported);
        $this->assertDirectoryDoesNotExist($target . '/old');
        $this->assertDirectoryExists($target . '/menu');
        $this->assertFileExists($target . '/menu/keep.yaml');

        $round = $this->archive->collectOutlines([$target]);
        foreach (['default', 'home'] as $name) {
            $this->assertSame($outlines[$name]['title'], $round[$name]['title']);
            $this->assertSame($outlines[$name]['preset'], $round[$name]['preset']);
            $this->assertEquals($outlines[$name]['config'], $round[$name]['config']);
        }

        $menuNames = $this->archive->importMenus($extractDir . '/menus', $target);
        $this->assertContains('main_menu', $menuNames);
        $menuRaw = file_get_contents($target . '/menu/main_menu.yaml');
        $menuData = \call_user_func($this->parse, $menuRaw);
        $origMenu = \call_user_func($this->parse, file_get_contents($this->customConfig . '/menu/main_menu.yaml'));
        $this->assertEquals($origMenu, $menuData);

        $readPos = $this->archive->readPositions($extractDir . '/positions');
        $this->assertEquals($widgets['sidebar-1'], $readPos['sidebar-1']);
        $this->assertEquals($widgets['sidebar-2'], $readPos['sidebar-2']);

        $targetMedia = $this->tmpRoot . '/target-media';
        @mkdir($targetMedia, 0755, true);
        $copied = $this->archive->importFiles(
            $extractDir . '/files',
            static function ($uri) use ($targetMedia) {
                if (strpos($uri, 'gantry-media://') === 0) {
                    return $targetMedia . '/' . substr($uri, \strlen('gantry-media://'));
                }

                return false;
            }
        );
        $this->assertSame(1, $copied);
        $this->assertFileEquals($this->mediaDir . '/logo.png', $targetMedia . '/logo.png');
    }

    /**
     * @return void
     */
    public function testArchiveWithoutOutlinesKeepsExistingOutlines()
    {
        $source = $this->tmpRoot . '/no-outlines/outlines';
        @mkdir($source, 0755, true);
        file_put_contents($source . '/outlines.yaml', \call_user_func($this->dump, []));

        $target = $this->tmpRoot . '/target-keep';
        @mkdir($target . '/existing', 0755, true);
        file_put_contents($target . '/existing/index.yaml', \call_user_func($this->dump, ['keep' => true]));

        $this->assertSame([], $this->archive->importOutlines($source, $target, true));
        $this->assertFileExists($target . '/existing/index.yaml');
    }

    /**
     * @return void
     */
    public function testImportSkipsAssignmentsForOtherPlatforms()
    {
        $dirs = [$this->customConfig, $this->themeConfig];
        $outlines = $this->archive->collectOutlines($dirs);
        $exported = [
            'export' => [
                'gantry' => ['version' => '5.test', 'format' => 1],
                'platform' => ['name' => 'joomla', 'version' => '4'],
                'theme' => ['name' => 'test'],
            ],
            'outlines' => $outlines,
            'positions' => [],
            'menus' => [],
            'content' => [],
            'categories' => [],
            'files' => [],
        ];

        $zipPath = $this->tmpRoot . '/joomla-export.zip';
        $this->buildZip($exported, $zipPath, static function () {
            return false;
        });

        $extractDir = $this->tmpRoot . '/joomla-extracted';
        @mkdir($extractDir, 0755, true);
        $zip = new \ZipArchive();
        $zip->open($zipPath);
        $zip->extractTo($extractDir);
        $zip->close();

        // Ensure assignments exist in the extracted tree for home.
        $this->assertFileExists($extractDir . '/outlines/home/assignments.yaml');

        $target = $this->tmpRoot . '/target-no-assign';
        @mkdir($target, 0755, true);

        $this->archive->importOutlines($extractDir . '/outlines', $target, false);
        $this->assertFileDoesNotExist($target . '/home/assignments.yaml');
        $this->assertFileExists($target . '/home/index.yaml');
    }

    /**
     * @return void
     */
    public function testUnsafeZipRejected()
    {
        $cases = [
            '../evil.yaml',
            'outlines/x/../../evil.yaml',
            '/abs.yaml',
            'C:/win.yaml',
        ];

        foreach ($cases as $entry) {
            $zipPath = $this->tmpRoot . '/unsafe-' . md5($entry) . '.zip';
            $zip = new \ZipArchive();
            $zip->open($zipPath, \ZipArchive::CREATE);
            $zip->addFromString($entry, 'x');
            $zip->close();

            $zip = new \ZipArchive();
            $zip->open($zipPath);
            try {
                $this->archive->assertSafeZip($zip);
                $this->fail('Expected RuntimeException for entry: ' . $entry);
            } catch (\RuntimeException $e) {
                $this->assertSame(400, $e->getCode());
            }
            $zip->close();
        }

        // NUL in name: try zip, otherwise test isSafeEntryName.
        $nulEntry = "outlines/a\0b.yaml";
        $this->assertFalse(OutlineArchive::isSafeEntryName($nulEntry));

        $zipPath = $this->tmpRoot . '/unsafe-nul.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $added = @$zip->addFromString($nulEntry, 'x');
        $zip->close();
        $zip = new \ZipArchive();
        $stored = ($added && $zip->open($zipPath) === true) ? $zip->getNameIndex(0) : false;
        if ($stored !== false) {
            $zip->close();
        }
        // libzip may truncate the name at NUL; only a stored NUL is a real case.
        if (is_string($stored) && strpos($stored, "\0") !== false) {
            $zip = new \ZipArchive();
            $zip->open($zipPath);
            try {
                $this->archive->assertSafeZip($zip);
                $this->fail('Expected RuntimeException for NUL entry');
            } catch (\RuntimeException $e) {
                $this->assertSame(400, $e->getCode());
            }
            $zip->close();
        }

        $this->assertTrue(OutlineArchive::isSafeEntryName('outlines/default/index.yaml'));
    }

    /**
     * @return void
     */
    public function testSymlinkInExtractedTreeRejected()
    {
        $source = $this->tmpRoot . '/symlink-source';
        @mkdir($source . '/default', 0755, true);
        file_put_contents(
            $source . '/default/index.yaml',
            \call_user_func($this->dump, ['title' => 'Default'])
        );

        $link = $source . '/default/evil-link';
        $created = @symlink($this->tmpRoot, $link);
        if (!$created) {
            $this->markTestSkipped('symlink() not available');
        }

        $target = $this->tmpRoot . '/symlink-target';
        @mkdir($target . '/stale', 0755, true);
        file_put_contents($target . '/stale/index.yaml', 'stale');

        try {
            $this->archive->importOutlines($source, $target, true);
            $this->fail('Expected RuntimeException for symlink tree');
        } catch (\RuntimeException $e) {
            $this->assertSame(400, $e->getCode());
        }

        $this->assertDirectoryExists($target . '/stale');
        $this->assertFileExists($target . '/stale/index.yaml');
    }

    /**
     * @return void
     */
    public function testInvalidYamlRejectedBeforeWriting()
    {
        $source = $this->tmpRoot . '/bad-yaml-source';
        @mkdir($source . '/default', 0755, true);
        file_put_contents($source . '/default/index.yaml', '{not json');

        // Ensure parse throws on invalid content.
        $parse = $this->parse;
        $dump = $this->dump;
        $strictParse = static function ($s) use ($parse) {
            try {
                $result = \call_user_func($parse, $s);
            } catch (\Exception $e) {
                throw new \RuntimeException('Invalid export file.', 400);
            }
            // JSON mode already throws; for YAML mode invalid may return null or throw.
            if ($result === null && trim((string) $s) !== '' && strtolower(trim((string) $s)) !== 'null') {
                // Symfony Yaml may return null for empty; '{not json' should fail.
                if (strpos((string) $s, '{not') !== false) {
                    throw new \RuntimeException('Invalid export file.', 400);
                }
            }

            return $result;
        };

        $archive = new OutlineArchive($strictParse, $dump);

        $target = $this->tmpRoot . '/bad-yaml-target';
        @mkdir($target . '/stale', 0755, true);
        file_put_contents($target . '/stale/index.yaml', 'stale');

        try {
            $archive->importOutlines($source, $target, true);
            $this->fail('Expected RuntimeException for invalid yaml');
        } catch (\RuntimeException $e) {
            $this->assertSame(400, $e->getCode());
        }

        $this->assertDirectoryExists($target . '/stale');
        $this->assertFileExists($target . '/stale/index.yaml');
        $this->assertDirectoryDoesNotExist($target . '/default');
    }

    /**
     * @return void
     */
    public function testImportFilesRejectsUnsafe()
    {
        $filesDir = $this->tmpRoot . '/unsafe-files';
        @mkdir($filesDir . '/gantry-media', 0755, true);
        @mkdir($filesDir . '/gantry-theme/config', 0755, true);
        @mkdir($filesDir . '/other-stream', 0755, true);

        file_put_contents($filesDir . '/gantry-media/shell.php', '<?php');
        file_put_contents($filesDir . '/gantry-media/x.svg', '<svg><script>alert(1)</script></svg>');
        file_put_contents($filesDir . '/gantry-theme/config/x.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        file_put_contents($filesDir . '/other-stream/a.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        $dest = $this->tmpRoot . '/import-files-dest';
        @mkdir($dest, 0755, true);

        $copied = $this->archive->importFiles(
            $filesDir,
            static function ($uri) use ($dest) {
                $path = preg_replace('#^[a-z-]+://#', '', $uri);

                return $dest . '/' . $path;
            }
        );
        $this->assertSame(0, $copied);

        // Clean good file.
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        file_put_contents($filesDir . '/gantry-media/ok.png', $png);

        $copied = $this->archive->importFiles(
            $filesDir,
            static function ($uri) use ($dest) {
                if (strpos($uri, 'gantry-media://') !== 0) {
                    return false;
                }

                return $dest . '/' . substr($uri, \strlen('gantry-media://'));
            }
        );
        $this->assertSame(1, $copied);
        $this->assertFileExists($dest . '/ok.png');
    }

    /**
     * Build fixture site A under temp dir.
     *
     * @return void
     */
    protected function buildSiteA()
    {
        $this->siteDir = $this->tmpRoot . '/site';
        $this->customConfig = $this->siteDir . '/custom/config';
        $this->themeConfig = $this->siteDir . '/theme/config';
        $this->mediaDir = $this->siteDir . '/media';

        $dump = $this->dump;

        // Theme default outline.
        $this->writeYaml($this->themeConfig . '/default/index.yaml', ['name' => 'default']);
        $this->writeYaml($this->themeConfig . '/default/layout.yaml', [
            'preset' => ['name' => 'default'],
        ]);
        $this->writeYaml($this->themeConfig . '/default/styles.yaml', ['value' => 'theme-styles']);
        $this->writeYaml($this->themeConfig . '/default/page/head.yaml', ['title' => 'Head']);
        $this->writeYaml($this->themeConfig . '/default/particles/logo.yaml', [
            'image' => 'gantry-media://logo.png',
        ]);
        $this->writeYaml($this->themeConfig . '/default/content/blog.yaml', ['enabled' => true]);

        // Custom overrides / home / menu.
        $this->writeYaml($this->customConfig . '/default/styles.yaml', ['value' => 'custom-styles']);
        $this->writeYaml($this->customConfig . '/home/index.yaml', ['name' => 'home']);
        $this->writeYaml($this->customConfig . '/home/layout.yaml', [
            'preset' => ['name' => 'home'],
        ]);
        $this->writeYaml($this->customConfig . '/home/assignments.yaml', ['menu' => ['home']]);
        $this->writeYaml($this->customConfig . '/menu/main_menu.yaml', [
            'settings' => ['key' => 'main'],
        ]);

        // Ignored entries.
        $this->writeYaml($this->customConfig . '/.hidden/index.yaml', ['hidden' => true]);
        @mkdir($this->customConfig . '/bad name', 0755, true);
        $this->writeYaml($this->customConfig . '/bad name/index.yaml', ['bad' => true]);

        // Media.
        @mkdir($this->mediaDir, 0755, true);
        file_put_contents(
            $this->mediaDir . '/logo.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );
    }

    /**
     * Mirrors Html/Export.php zip layout.
     *
     * @param array $exported
     * @param string $zipPath
     * @param callable $resolveFile
     * @return void
     */
    protected function buildZip(array $exported, $zipPath, callable $resolveFile)
    {
        $dump = $this->dump;
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('export.yaml', \call_user_func($dump, $exported['export']));

        if (!empty($exported['positions'])) {
            foreach ($exported['positions'] as $key => $position) {
                foreach ($position['items'] as $module => $data) {
                    $zip->addFromString(
                        "positions/{$key}/{$module}.yaml",
                        \call_user_func($dump, $data)
                    );
                }

                $meta = $position;
                $meta['ordering'] = array_keys($position['items']);
                unset($meta['items']);
                $zip->addFromString("positions/{$key}.yaml", \call_user_func($dump, $meta));
            }
        }

        $outlineMeta = [];
        if (!empty($exported['outlines'])) {
            foreach ($exported['outlines'] as $outline => $data) {
                $meta = $data;
                if (!empty($data['config'])) {
                    foreach ($data['config'] as $name => $config) {
                        if (\in_array($name, ['particles', 'page'], true)) {
                            foreach ((array) $config as $sub => $subconfig) {
                                $zip->addFromString(
                                    "outlines/{$outline}/{$name}/{$sub}.yaml",
                                    \call_user_func($dump, $subconfig)
                                );
                            }
                        } else {
                            $zip->addFromString(
                                "outlines/{$outline}/{$name}.yaml",
                                \call_user_func($dump, $config)
                            );
                        }
                    }
                }
                unset($meta['config']);
                $outlineMeta[$outline] = $meta;
            }
        }
        $zip->addFromString('outlines/outlines.yaml', \call_user_func($dump, $outlineMeta));

        if (!empty($exported['menus'])) {
            foreach ($exported['menus'] as $menu => $data) {
                $zip->addFromString("menus/{$menu}.yaml", \call_user_func($dump, $data));
            }
        }

        if (!empty($exported['files'])) {
            foreach ($exported['files'] as $stream => $fileMap) {
                foreach ($fileMap as $path => $uri) {
                    $filename = \call_user_func($resolveFile, $uri);
                    if ($filename && is_file($filename)) {
                        $zip->addFile($filename, "files/{$stream}/{$path}");
                    }
                }
            }
        }

        $zip->close();
    }

    /**
     * @param string $path
     * @param mixed $data
     * @return void
     */
    protected function writeYaml($path, $data)
    {
        $dir = \dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents($path, \call_user_func($this->dump, $data));
    }

    /**
     * @param string $path
     * @return void
     */
    protected function deleteTree($path)
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $entries = @scandir($path);
        if ($entries !== false) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $this->deleteTree($path . DIRECTORY_SEPARATOR . $entry);
            }
        }
        @rmdir($path);
    }
}
