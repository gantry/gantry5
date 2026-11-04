<?php

namespace Gantry\Tests\PHP83\Component\Filesystem;

use Gantry\Component\Filesystem\PathGuard;
use PHPUnit\Framework\TestCase;

/**
 * @package Gantry\Tests\PHP83\Component\Filesystem
 */
class PathGuardTest extends TestCase
{
    /** @var string */
    protected $site;

    /** @var PathGuard */
    protected $guard;

    /** @var bool */
    protected $hasSymlink = false;

    protected function setUp(): void
    {
        $this->site = sys_get_temp_dir() . '/gantry_pathguard_' . uniqid('', true);

        $dirs = [
            $this->site,
            $this->site . '/images',
            $this->site . '/images2',
            $this->site . '/media',
            $this->site . '/outside',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        file_put_contents($this->site . '/configuration.php', "<?php\n");
        file_put_contents($this->site . '/wp-config.php', "<?php\n");
        file_put_contents($this->site . '/images/.env', "SECRET=1\n");
        file_put_contents($this->site . '/images/shell.php', "<?php\n");
        file_put_contents($this->site . '/images/evil.php.jpg', "x\n");
        file_put_contents($this->site . '/images2/x.png', "x\n");
        file_put_contents($this->site . '/outside/secret.txt', "secret\n");
        file_put_contents($this->site . '/media/readme.txt', "media\n");

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        file_put_contents($this->site . '/images/logo.png', $png);

        $this->hasSymlink = false;
        if (function_exists('symlink')) {
            $link = $this->site . '/images/link';
            // @ is intentional: some environments disallow symlink().
            if (@symlink($this->site . '/outside', $link)) {
                $this->hasSymlink = true;
            }
        }

        $this->guard = new PathGuard(
            $this->site,
            [$this->site . '/images', $this->site . '/media'],
            [$this->site . '/images']
        );
    }

    protected function tearDown(): void
    {
        if ($this->hasSymlink) {
            $link = $this->site . '/images/link';
            if (is_link($link) || file_exists($link)) {
                unlink($link);
            }
        }

        $this->removeTree($this->site);
    }

    /**
     * @param string $dir
     * @return void
     */
    protected function removeTree($dir)
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
            if (is_link($path) || is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeTree($path);
            }
        }

        rmdir($dir);
    }

    /**
     * Build absolute candidate the same way the controller does.
     *
     * @param string $p
     * @return string
     */
    protected function candidate($p)
    {
        $clean = PathGuard::assertCleanInput($p);

        return $this->site . '/' . ltrim($clean, '/');
    }

    public function testPlainDotDotTraversal()
    {
        $this->expectException(\RuntimeException::class);
        PathGuard::assertCleanInput('images/../configuration.php');
    }

    public function testLeadingDotDot()
    {
        $this->expectException(\RuntimeException::class);
        PathGuard::assertCleanInput('../configuration.php');
    }

    public function testEncodedTraversal()
    {
        $this->expectException(\RuntimeException::class);
        PathGuard::assertCleanInput('images/%2e%2e/configuration.php');
    }

    public function testDoubleEncodedTraversal()
    {
        $this->expectException(\RuntimeException::class);
        PathGuard::assertCleanInput('images/%252e%252e/configuration.php');
    }

    public function testBackslashTraversal()
    {
        $this->expectException(\RuntimeException::class);
        PathGuard::assertCleanInput('images\\..\\configuration.php');
    }

    public function testNulByte()
    {
        $this->expectException(\RuntimeException::class);
        PathGuard::assertCleanInput('images/logo.png' . "\0" . '.php');
    }

    public function testSymlinkEscape()
    {
        if (!$this->hasSymlink) {
            $this->markTestSkipped('symlink() not available');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $candidate = $this->candidate('images/link/secret.txt');
        $this->guard->resolveExisting($candidate, false);
    }

    public function testSiblingPrefixRejected()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $candidate = $this->candidate('images2/x.png');
        $this->guard->resolveExisting($candidate, false);
    }

    public function testConfigFilesOutsideRoots()
    {
        $this->assertTrue(PathGuard::isForbiddenFile('configuration.php'));
        $this->assertTrue(PathGuard::isForbiddenFile('wp-config.php'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $candidate = $this->candidate('configuration.php');
        $this->guard->resolveExisting($candidate, false);
    }

    public function testWpConfigOutsideRoots()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $candidate = $this->candidate('wp-config.php');
        $this->guard->resolveExisting($candidate, false);
    }

    public function testDotfilesForbidden()
    {
        $this->assertTrue(PathGuard::isForbiddenFile('.env'));
        $this->assertTrue(PathGuard::isForbiddenFile('.htaccess'));
    }

    public function testShellAndDoubleExtensionForbidden()
    {
        $this->assertTrue(PathGuard::isForbiddenFile('shell.php'));
        $this->assertTrue(PathGuard::isForbiddenFile('evil.php.jpg'));
    }

    public function testNormalImageResolves()
    {
        $candidate = $this->candidate('images/logo.png');
        $real = $this->guard->resolveExisting($candidate, false);
        $this->assertSame(realpath($this->site . '/images/logo.png'), $real);
        $this->assertFalse(PathGuard::isForbiddenFile('images/logo.png'));
        $this->assertFalse(PathGuard::isForbiddenFile('logo.png'));
    }

    public function testWriteOnReadOnlyRootRejected()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $candidate = $this->candidate('media/readme.txt');
        $this->guard->resolveExisting($candidate, true);
    }

    public function testAssertUploadAllowedAcceptsPng()
    {
        $png = $this->site . '/images/logo.png';
        PathGuard::assertUploadAllowed('logo.png', $png, null);
        $this->assertTrue(true);
    }

    public function testAssertUploadAllowedRejectsPhpName()
    {
        $png = $this->site . '/images/logo.png';
        $this->expectException(\RuntimeException::class);
        PathGuard::assertUploadAllowed('logo.php', $png, null);
    }

    public function testAssertUploadAllowedRejectsPhtmlName()
    {
        $png = $this->site . '/images/logo.png';
        $this->expectException(\RuntimeException::class);
        PathGuard::assertUploadAllowed('logo.phtml', $png, null);
    }

    public function testAssertUploadAllowedRejectsPharName()
    {
        $png = $this->site . '/images/logo.png';
        $this->expectException(\RuntimeException::class);
        PathGuard::assertUploadAllowed('logo.phar', $png, null);
    }

    public function testAssertUploadAllowedRejectsHtaccess()
    {
        $png = $this->site . '/images/logo.png';
        $this->expectException(\RuntimeException::class);
        PathGuard::assertUploadAllowed('.htaccess', $png, null);
    }

    public function testAssertUploadAllowedRejectsDoubleExtension()
    {
        $png = $this->site . '/images/logo.png';
        $this->expectException(\RuntimeException::class);
        PathGuard::assertUploadAllowed('logo.php.png', $png, null);
    }

    public function testAssertUploadAllowedRejectsMimeMismatch()
    {
        $fake = $this->site . '/images/fake.png';
        file_put_contents($fake, "this is not an image\n");
        $this->expectException(\RuntimeException::class);
        PathGuard::assertUploadAllowed('fake.png', $fake, null);
    }

    public function testAssertUploadAllowedRejectsScriptSvg()
    {
        $svg = $this->site . '/images/bad.svg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->expectException(\RuntimeException::class);
        PathGuard::assertUploadAllowed('bad.svg', $svg, null);
    }

    public function testAssertUploadAllowedAcceptsCleanSvg()
    {
        $svg = $this->site . '/images/good.svg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>');
        PathGuard::assertUploadAllowed('good.svg', $svg, null);
        $this->assertTrue(true);
    }

    public function testExtraAllowedExtensionsList()
    {
        $png = $this->site . '/images/logo.png';

        try {
            PathGuard::assertUploadAllowed('logo.png', $png, ['jpg']);
            $this->fail('Expected png to be rejected when extra list is jpg only');
        } catch (\RuntimeException $e) {
            $this->assertSame(400, $e->getCode());
        }

        $svg = $this->site . '/images/good2.svg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>');
        PathGuard::assertUploadAllowed('good2.svg', $svg, ['jpg']);
        $this->assertTrue(true);
    }
}
