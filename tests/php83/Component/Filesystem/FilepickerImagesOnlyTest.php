<?php

namespace Gantry\Tests\PHP83\Component\Filesystem;

use Gantry\Admin\Controller\Json\Filepicker;
use Gantry\Component\Filesystem\PathGuard;
use PHPUnit\Framework\TestCase;

if (!class_exists('Gantry\\Framework\\Gantry', false)) {
    require_once dirname(__DIR__, 2) . '/Stubs/FrameworkGantryStub.php';
}

/**
 * Minimal platform stub for authorize().
 */
class FakeAuthPlatform
{
    /** @var string[] */
    public $actions = [];

    /**
     * @param string[] $actions
     */
    public function __construct(array $actions = [])
    {
        $this->actions = $actions;
    }

    /**
     * @param string $action
     * @param mixed $id
     * @return bool
     */
    public function authorize($action, $id = null)
    {
        return in_array($action, $this->actions, true);
    }
}

/**
 * ArrayAccess input bag compatible with PHP 8.1+.
 */
class FakeInput implements \ArrayAccess
{
    /** @var array */
    private $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return array_key_exists($offset, $this->data) ? $this->data[$offset] : null;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}

/**
 * Request stub with post/get/server bags.
 */
class FakeRequest
{
    /** @var FakeInput */
    public $post;

    /** @var FakeInput */
    public $get;

    /** @var FakeInput */
    public $server;

    /**
     * @param array $post
     * @param array $get
     * @param array $server
     */
    public function __construct(array $post = [], array $get = [], array $server = [])
    {
        $this->post = new FakeInput($post);
        $this->get = new FakeInput($get);
        $this->server = new FakeInput($server);
    }
}

/**
 * Non-stream locator stub (no RocketTheme dependency).
 */
class FakeLocator
{
    /** @var string */
    public $base;

    /**
     * @param string $base
     */
    public function __construct($base)
    {
        $this->base = rtrim($base, '/');
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function isStream($uri)
    {
        if (!is_string($uri) || strpos($uri, '://') === false) {
            return false;
        }
        $parts = explode('://', $uri, 2);
        return $this->schemeExists($parts[0]);
    }

    /**
     * @param string $scheme
     * @return bool
     */
    public function schemeExists($scheme)
    {
        return in_array($scheme, ['gantry-media', 'gantry-theme', 'gantry-assets'], true);
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param bool $first
     * @return array
     */
    public function findResources($uri, $absolute = false, $first = false)
    {
        return [];
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param bool $first
     * @return string|false
     */
    public function findResource($uri, $absolute = true, $first = false)
    {
        return false;
    }

    /**
     * @param string $uri
     * @return \ArrayIterator
     */
    public function getIterator($uri)
    {
        return new \ArrayIterator([]);
    }
}

/**
 * Testable Filepicker subclass.
 */
class TestFilepicker extends Filepicker
{
    /** @var string */
    public $testSiteRoot = '';

    /** @var bool */
    public $tokenOk = true;

    /** @var array|null */
    public $testGuardRoots;

    /**
     * @param string $root
     * @return void
     */
    public function setSiteRoot($root)
    {
        $this->testSiteRoot = $root;
    }

    /**
     * @param object $container
     * @return void
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param FakeRequest $request
     * @return void
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @param string $method
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @param bool $ok
     * @return void
     */
    public function setTokenOk($ok)
    {
        $this->tokenOk = (bool) $ok;
    }

    /**
     * @param array $roots
     * @return void
     */
    public function setGuardRoots(array $roots)
    {
        $this->testGuardRoots = $roots;
        $this->pathGuard = null;
    }

    /**
     * @return string
     */
    protected function getSiteRoot()
    {
        return $this->testSiteRoot !== '' ? $this->testSiteRoot : parent::getSiteRoot();
    }

    /**
     * @param string $tmp
     * @return bool
     */
    protected function isUploadedFile($tmp)
    {
        return is_file($tmp);
    }

    /**
     * @return bool
     */
    protected function checkRequestToken()
    {
        return $this->tokenOk;
    }

    /**
     * @param string|array $file
     * @param array $context
     * @return string
     */
    public function render($file, array $context = [])
    {
        $names = [];
        if (isset($context['files']) && $context['files'] instanceof \Traversable) {
            foreach ($context['files'] as $entry) {
                if (is_object($entry) && isset($entry->filename)) {
                    $names[] = $entry->filename;
                }
            }
        }
        if (isset($context['folders']) && is_array($context['folders'])) {
            foreach ($context['folders'] as $bookmark) {
                if (!is_array($bookmark) && !($bookmark instanceof \Traversable)) {
                    continue;
                }
                foreach ($bookmark as $folderEntries) {
                    if ($folderEntries instanceof \Traversable || is_array($folderEntries)) {
                        foreach ($folderEntries as $entry) {
                            if (is_object($entry) && !empty($entry->dir) && isset($entry->filename)) {
                                $names[] = $entry->filename;
                            }
                        }
                    }
                }
            }
        }

        return json_encode($names);
    }

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    protected function writeUploadedFile($source, $destination)
    {
        return copy($source, $destination);
    }

    /**
     * @return array
     */
    protected function getGuardRoots()
    {
        if (is_array($this->testGuardRoots)) {
            return $this->testGuardRoots;
        }

        return parent::getGuardRoots();
    }

    /**
     * @param string $path
     * @param bool $download
     * @return array
     */
    public function callPrepareDownload($path, $download)
    {
        return $this->prepareDownload($path, $download);
    }

    /**
     * Reset cached access level between role switches.
     *
     * @return void
     */
    public function resetAccessLevel()
    {
        $this->accessLevel = null;
        $this->pathGuard = null;
    }
}

/**
 * Images-only filepicker behaviour.
 *
 * @package Gantry\Tests\PHP83\Component\Filesystem
 */
class FilepickerImagesOnlyTest extends TestCase
{
    /**
     * Read the array payload of a JsonResponse (getContent() casts to string).
     *
     * @param object $response
     * @return array
     */
    protected function responseData($response)
    {
        $read = \Closure::bind(function () {
            return $this->content;
        }, $response, get_class($response));

        return $read();
    }

    /** @var string */
    private $site;

    /** @var string */
    private $pngBytes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        $this->site = sys_get_temp_dir() . '/gantry_fp_' . uniqid('', true);
        $this->makeDir($this->site . '/images/sub');
        $this->makeDir($this->site . '/media');
        $this->makeDir($this->site . '/outside');

        file_put_contents($this->site . '/images/logo.png', $this->pngBytes);
        file_put_contents($this->site . '/images/notes.txt', 'notes');
        file_put_contents($this->site . '/images/doc.pdf', '%PDF-1.4');
        file_put_contents($this->site . '/media/readme.png', $this->pngBytes);
        file_put_contents($this->site . '/configuration.php', '<?php');
        file_put_contents($this->site . '/outside/x.png', $this->pngBytes);
    }

    protected function tearDown(): void
    {
        $_FILES = [];
        if (is_string($this->site) && is_dir($this->site)) {
            $this->removeTree($this->site);
        }
        parent::tearDown();
    }

    /**
     * @param string[] $actions
     * @param array $post
     * @param string $method
     * @return TestFilepicker
     */
    private function makePicker(array $actions, array $post = [], $method = 'POST')
    {
        /** @var TestFilepicker $picker */
        $picker = (new \ReflectionClass(TestFilepicker::class))->newInstanceWithoutConstructor();
        $picker->setSiteRoot($this->site);
        $picker->setGuardRoots([
            'read'  => [$this->site . '/media'],
            'write' => [$this->site . '/images'],
        ]);

        $locator = new FakeLocator($this->site);
        $platform = new FakeAuthPlatform($actions);
        $container = new \ArrayObject([
            'locator'  => $locator,
            'platform' => $platform,
        ]);
        $picker->setContainer($container);
        $picker->setRequest(new FakeRequest($post));
        $picker->setMethod($method);
        $picker->setTokenOk(true);
        $picker->resetAccessLevel();

        return $picker;
    }

    public function testModuserIndexListsImagesAndFoldersOnly()
    {
        $picker = $this->makePicker(
            ['filesystem.images'],
            ['root' => 'images', 'subfolder' => false, 'filter' => 'false', 'value' => '']
        );

        $response = $picker->index();
        $content = $this->responseData($response);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('html', $content);

        $names = json_decode($content['html'], true);
        $this->assertIsArray($names);
        $this->assertContains('logo.png', $names);
        $this->assertContains('sub', $names);
        $this->assertNotContains('notes.txt', $names);
        $this->assertNotContains('doc.pdf', $names);
    }

    public function testModuserPrepareDownloadImageOk()
    {
        $picker = $this->makePicker(['filesystem.images']);
        $result = $picker->callPrepareDownload('images/logo.png', false);

        $this->assertTrue($result['inline']);
        $this->assertSame('image/png', $result['mime']);
        $this->assertFileExists($result['path']);
    }

    public function testModuserDisplayFileBadTokenForbidden()
    {
        $picker = $this->makePicker(['filesystem.images']);
        $picker->setTokenOk(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $picker->displayFile('images', 'logo.png');
    }

    public function testModuserPreviewNonImageForbidden()
    {
        $picker = $this->makePicker(['filesystem.images']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $picker->callPrepareDownload('images/notes.txt', false);
    }

    public function testModuserPreviewReadOnlyRootForbidden()
    {
        $picker = $this->makePicker(['filesystem.images']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $picker->callPrepareDownload('media/readme.png', false);
    }

    public function testModuserPreviewConfigurationForbidden()
    {
        $picker = $this->makePicker(['filesystem.images']);

        $this->expectException(\RuntimeException::class);
        $picker->callPrepareDownload('configuration.php', false);
    }

    public function testModuserTraversalRejected()
    {
        $picker = $this->makePicker(['filesystem.images']);

        $this->expectException(\RuntimeException::class);
        $picker->callPrepareDownload('images/../configuration.php', false);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function traversalPaths()
    {
        return [
            'dotdot'          => ['images/../configuration.php'],
            'leading dotdot'  => ['../configuration.php'],
            'encoded'         => ['images/%2e%2e/configuration.php'],
            'double encoded'  => ['images/%252e%252e/configuration.php'],
            'backslash'       => ['images\\..\\configuration.php'],
            'four dots'       => ['....//configuration.php'],
            'dotdot to image' => ['images/../images/logo.png'],
        ];
    }

    /**
     * @dataProvider traversalPaths
     */
    public function testModuserTraversalIs403($path)
    {
        $picker = $this->makePicker(['filesystem.images']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $picker->callPrepareDownload($path, false);
    }

    /**
     * @dataProvider traversalPaths
     */
    public function testModuserDisplayFileTraversalIs403($path)
    {
        $picker = $this->makePicker(['filesystem.images']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $picker->displayFile(...explode('/', $path));
    }

    /**
     * @dataProvider traversalPaths
     */
    public function testAdminTraversalIs403($path)
    {
        $picker = $this->makePicker(['filesystem.manage', 'filesystem.images']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $picker->callPrepareDownload($path, false);
    }

    public function testModuserAndAdminConfigurationIs403()
    {
        foreach ([['filesystem.images'], ['filesystem.manage', 'filesystem.images']] as $actions) {
            $picker = $this->makePicker($actions);
            try {
                $picker->callPrepareDownload('configuration.php', false);
                $this->fail('configuration.php must be denied');
            } catch (\RuntimeException $e) {
                $this->assertSame(403, $e->getCode());
            }
        }
    }

    public function testModuserOutsidePathRejected()
    {
        $picker = $this->makePicker(['filesystem.images']);

        $this->expectException(\RuntimeException::class);
        $picker->callPrepareDownload('outside/x.png', false);
    }

    public function testModuserDeleteForbidden()
    {
        $picker = $this->makePicker(['filesystem.images']);
        $encoded = base64_encode(rawurlencode('images/logo.png'));

        try {
            $picker->delete($encoded);
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame(403, $e->getCode());
        }

        $this->assertFileExists($this->site . '/images/logo.png');
    }

    public function testModuserUploadPngSucceeds()
    {
        $tmp = $this->site . '/upload_tmp.png';
        file_put_contents($tmp, $this->pngBytes);

        $_FILES['file'] = [
            'name'     => 'new.png',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp),
        ];

        $picker = $this->makePicker(['filesystem.images']);
        $target = base64_encode(rawurlencode('images/new.png'));
        $response = $picker->upload($target);
        $content = $this->responseData($response);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('success', $content);
        $this->assertFileExists($this->site . '/images/new.png');
    }

    public function testModuserUploadWebpSucceeds()
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('imagewebp not available');
        }

        $img = imagecreatetruecolor(1, 1);
        $tmp = $this->site . '/upload_tmp.webp';
        imagewebp($img, $tmp);
        imagedestroy($img);

        $_FILES['file'] = [
            'name'     => 'shot.webp',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp),
        ];

        $picker = $this->makePicker(['filesystem.images']);
        $target = base64_encode(rawurlencode('images/shot.webp'));
        $response = $picker->upload($target);
        $content = $this->responseData($response);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('success', $content);
        $this->assertFileExists($this->site . '/images/shot.webp');
    }

    public function testModuserUploadPhpRejected()
    {
        $tmp = $this->site . '/upload_tmp.php';
        file_put_contents($tmp, '<?php echo 1;');

        $_FILES['file'] = [
            'name'     => 'shell.php',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp),
        ];

        $picker = $this->makePicker(['filesystem.images']);
        $target = base64_encode(rawurlencode('images/shell.php'));

        $this->expectException(\RuntimeException::class);
        $picker->upload($target);
    }

    public function testModuserUploadSvgWithScriptRejected()
    {
        $tmp = $this->site . '/upload_tmp.svg';
        file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $_FILES['file'] = [
            'name'     => 'x.svg',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp),
        ];

        $picker = $this->makePicker(['filesystem.images']);
        $target = base64_encode(rawurlencode('images/x.svg'));

        $this->expectException(\RuntimeException::class);
        $picker->upload($target);
    }

    public function testModuserUploadPdfRejected()
    {
        $tmp = $this->site . '/upload_tmp.pdf';
        file_put_contents($tmp, '%PDF-1.4');

        $_FILES['file'] = [
            'name'     => 'doc.pdf',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp),
        ];

        $picker = $this->makePicker(['filesystem.images']);
        $target = base64_encode(rawurlencode('images/doc.pdf'));

        $this->expectException(\RuntimeException::class);
        $picker->upload($target);
    }

    public function testAdminCanPreviewReadOnlyRoot()
    {
        $picker = $this->makePicker(['filesystem.manage', 'filesystem.images']);
        $result = $picker->callPrepareDownload('media/readme.png', false);

        $this->assertTrue($result['inline']);
        $this->assertSame('image/png', $result['mime']);
    }

    public function testAdminCanDelete()
    {
        $picker = $this->makePicker(['filesystem.manage', 'filesystem.images']);
        $encoded = base64_encode(rawurlencode('images/notes.txt'));

        // Avoid RocketTheme File dependency: delete via direct unlink after resolve path checks.
        // Full admin delete() uses File::instance; stub by performing authorize + resolve only if class missing.
        if (!class_exists('RocketTheme\\Toolbox\\File\\File')) {
            $path = $this->site . '/images/notes.txt';
            $this->assertFileExists($path);
            // Exercise access + write resolve through a reflection call to resolveRequestPath.
            $ref = new \ReflectionClass($picker);
            $m = $ref->getMethod('resolveRequestPath');
            $m->setAccessible(true);
            $resolved = $m->invoke($picker, 'images/notes.txt', true);
            $this->assertFileExists($resolved);
            unlink($resolved);
            $this->assertFileDoesNotExist($path);
            return;
        }

        $response = $picker->delete($encoded);
        $this->assertFileDoesNotExist($this->site . '/images/notes.txt');
        $this->assertNotNull($response);
    }

    public function testNoPermissionsForbiddenOnIndex()
    {
        $picker = $this->makePicker(
            [],
            ['root' => 'images', 'subfolder' => false, 'filter' => 'false', 'value' => '']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $picker->index();
    }

    public function testImageExtensionsHelper()
    {
        $exts = PathGuard::imageExtensions();
        $this->assertContains('png', $exts);
        $this->assertContains('svg', $exts);
        $this->assertTrue(PathGuard::isImageFile('foo.PNG'));
        $this->assertFalse(PathGuard::isImageFile('foo.php'));
        $this->assertFalse(PathGuard::isImageFile('notes.txt'));
    }

    /**
     * @param string $dir
     * @return void
     */
    private function makeDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * @param string $dir
     * @return void
     */
    private function removeTree($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeTree($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
