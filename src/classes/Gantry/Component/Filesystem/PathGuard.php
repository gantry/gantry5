<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework)
 * @currentDeveloper  Tiger12, LLC
 * @copyright Copyright (C) 2007 - 2022 Tiger12, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Filesystem;

/**
 * Path normalisation and allowlist checks (security hardening).
 *
 * @package Gantry\Component\Filesystem
 */
class PathGuard
{
    /** @var string */
    protected $siteRoot;

    /** @var string[] */
    protected $readRoots = [];

    /** @var string[] */
    protected $writeRoots = [];

    /**
     * @param string $siteRoot
     * @param string[] $readRoots
     * @param string[] $writeRoots
     */
    public function __construct($siteRoot, array $readRoots, array $writeRoots)
    {
        $realRoot = realpath($siteRoot);
        $this->siteRoot = $realRoot !== false ? $realRoot : (string) $siteRoot;

        foreach ($readRoots as $root) {
            $real = realpath($root);
            if ($real !== false && is_dir($real)) {
                $this->readRoots[] = $real;
            }
        }

        foreach ($writeRoots as $root) {
            $real = realpath($root);
            if ($real !== false && is_dir($real)) {
                $this->writeRoots[] = $real;
            }
        }

        // Write roots are always readable.
        foreach ($this->writeRoots as $root) {
            if (!in_array($root, $this->readRoots, true)) {
                $this->readRoots[] = $root;
            }
        }
    }

    /**
     * @param mixed $path
     * @return string
     */
    public static function assertCleanInput($path)
    {
        if (!is_string($path)) {
            throw new \RuntimeException('Invalid path.', 400);
        }

        if (strpos($path, "\0") !== false) {
            throw new \RuntimeException('Access denied.', 403);
        }

        $len = strlen($path);
        for ($i = 0; $i < $len; $i++) {
            if (ord($path[$i]) < 0x20) {
                throw new \RuntimeException('Invalid path.', 400);
            }
        }

        $decoded = $path;
        for ($round = 0; $round < 3; $round++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;

            if (strpos($decoded, "\0") !== false) {
                throw new \RuntimeException('Access denied.', 403);
            }

            self::assertNoDotDotSegments($decoded);
        }

        $decoded = str_replace('\\', '/', $decoded);
        self::assertNoDotDotSegments($decoded);

        return $decoded;
    }

    /**
     * @param string $path
     * @return void
     */
    protected static function assertNoDotDotSegments($path)
    {
        $normalised = str_replace('\\', '/', $path);
        $segments = preg_split('#[/\\\\]#', $normalised);

        if (!is_array($segments)) {
            throw new \RuntimeException('Invalid path.', 400);
        }

        foreach ($segments as $segment) {
            // Reject '..' and other dot-only segments ('...', '....').
            if (preg_match('/^\.{2,}$/', $segment)) {
                throw new \RuntimeException('Access denied.', 403);
            }
        }
    }

    /**
     * @param string $absolutePath
     * @param bool $write
     * @return string
     */
    public function resolveExisting($absolutePath, $write = false)
    {
        $real = realpath($absolutePath);
        if ($real === false || !file_exists($real)) {
            throw new \RuntimeException('File not found.', 404);
        }

        $roots = $write ? $this->writeRoots : $this->readRoots;
        if (!$this->isInsideRoots($real, $roots)) {
            throw new \RuntimeException('Access denied.', 403);
        }

        return $real;
    }

    /**
     * @param string $directory
     * @param string $filename
     * @return string
     */
    public function resolveNewFile($directory, $filename)
    {
        self::assertSafeFilename($filename);

        $realDir = realpath($directory);
        if ($realDir === false || !is_dir($realDir)) {
            throw new \RuntimeException('File not found.', 404);
        }

        if (!$this->isInsideRoots($realDir, $this->writeRoots)) {
            throw new \RuntimeException('Access denied.', 403);
        }

        return $realDir . '/' . $filename;
    }

    /**
     * @param string $path
     * @param string[] $roots
     * @return bool
     */
    protected function isInsideRoots($path, array $roots)
    {
        foreach ($roots as $root) {
            if ($path === $root) {
                return true;
            }
            $prefix = $root . DIRECTORY_SEPARATOR;
            if (strpos($path, $prefix) === 0) {
                return true;
            }
            // Also accept forward-slash form on Windows-style mixed paths.
            $prefixFwd = $root . '/';
            if (DIRECTORY_SEPARATOR !== '/' && strpos($path, $prefixFwd) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a path is inside any read root (used by callers for drive filtering).
     *
     * @param string $absolutePath
     * @param bool $write
     * @return bool
     */
    public function isUnderRoots($absolutePath, $write = false)
    {
        $real = realpath($absolutePath);
        if ($real === false) {
            return false;
        }

        $roots = $write ? $this->writeRoots : $this->readRoots;

        return $this->isInsideRoots($real, $roots);
    }

    /**
     * Whether a (possibly non-existing) directory can be created under a write root.
     *
     * @param string $directory
     * @return bool
     */
    public function canCreateDirectory($directory)
    {
        $current = $directory;
        while ($current !== '' && $current !== '/' && $current !== '.' && $current !== DIRECTORY_SEPARATOR) {
            $real = realpath($current);
            if ($real !== false) {
                return $this->isInsideRoots($real, $this->writeRoots);
            }
            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }
            $current = $parent;
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isForbiddenFile($path)
    {
        $basename = basename(str_replace('\\', '/', (string) $path));

        if ($basename === '' || $basename[0] === '.') {
            return true;
        }

        $lower = strtolower($basename);
        $blockedNames = [
            'configuration.php',
            'wp-config.php',
            'wp-config-sample.php',
            '.htaccess',
            '.user.ini',
            'php.ini',
            'web.config',
        ];
        if (in_array($lower, $blockedNames, true)) {
            return true;
        }

        if (preg_match('/\.(php\d*|phtml|phar|pht|phps|inc|cgi|pl|py|sh|asp|aspx|jsp)$/i', $basename)) {
            return true;
        }

        // Double-extension style: shell.php.jpg
        $parts = explode('.', $basename);
        if (count($parts) > 2) {
            // Skip the first part (name) and the last (final extension); check middle parts.
            $middle = array_slice($parts, 1, -1);
            foreach ($middle as $part) {
                if (preg_match('/^(php\d*|phtml|phar|pht)$/i', $part)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Image extensions allowed for restricted image-picker access.
     *
     * @return string[]
     */
    public static function imageExtensions()
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];
    }

    /**
     * Whether a path looks like an allowed image file name.
     *
     * @param string $path
     * @return bool
     */
    public static function isImageFile($path)
    {
        if (self::isForbiddenFile($path)) {
            return false;
        }

        $basename = basename(str_replace('\\', '/', (string) $path));
        $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        return $ext !== '' && in_array($ext, self::imageExtensions(), true);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function assertSafeFilename($name)
    {
        if (!is_string($name) || $name === '') {
            throw new \RuntimeException('Invalid filename.', 400);
        }

        if (strpos($name, '/') !== false || strpos($name, '\\') !== false || strpos($name, "\0") !== false) {
            throw new \RuntimeException('Invalid filename.', 400);
        }

        if ($name === '.' || $name === '..') {
            throw new \RuntimeException('Invalid filename.', 400);
        }

        if (strlen($name) > 255) {
            throw new \RuntimeException('Invalid filename.', 400);
        }

        if (self::isForbiddenFile($name)) {
            throw new \RuntimeException('File type not allowed.', 400);
        }

        return $name;
    }

    /**
     * @return array
     */
    public static function allowedUploadTypes()
    {
        return [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'avif' => ['image/avif'],
            'bmp'  => ['image/bmp', 'image/x-ms-bmp'],
            'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon'],
            'svg'  => ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'],
            'woff' => ['font/woff', 'application/font-woff', 'application/octet-stream'],
            'woff2'=> ['font/woff2', 'application/octet-stream'],
            'ttf'  => ['font/ttf', 'font/sfnt', 'application/x-font-ttf', 'application/font-sfnt', 'application/octet-stream'],
            'otf'  => ['font/otf', 'font/sfnt', 'application/vnd.ms-opentype', 'application/octet-stream'],
            'eot'  => ['application/vnd.ms-fontobject', 'application/octet-stream'],
            'mp4'  => ['video/mp4'],
            'webm' => ['video/webm', 'audio/webm'],
            'ogv'  => ['video/ogg', 'application/ogg'],
            'mov'  => ['video/quicktime'],
        ];
    }

    /**
     * @param string $filename
     * @param string $tmpFile
     * @param array|null $extraAllowedExtensions
     * @return void
     */
    public static function assertUploadAllowed($filename, $tmpFile, ?array $extraAllowedExtensions = null)
    {
        self::assertSafeFilename($filename);

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = self::allowedUploadTypes();

        if ($ext === '' || !isset($allowed[$ext])) {
            throw new \RuntimeException('File type not allowed.', 400);
        }

        $fontExts = ['woff', 'woff2', 'ttf', 'otf', 'eot'];
        $isSvg = ($ext === 'svg');
        $isFont = in_array($ext, $fontExts, true);

        if (is_array($extraAllowedExtensions) && !$isSvg && !$isFont) {
            $extra = array_map('strtolower', $extraAllowedExtensions);
            if (!in_array($ext, $extra, true)) {
                throw new \RuntimeException('File type not allowed.', 400);
            }
        }

        $mimes = $allowed[$ext];
        $detected = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $tmpFile);
                finfo_close($finfo);
            }
        }

        if ($detected !== null && $detected !== false) {
            if (!in_array($detected, $mimes, true)) {
                throw new \RuntimeException('File type not allowed.', 400);
            }
        } else {
            // Fallback when fileinfo is unavailable.
            $raster = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico'];
            if (in_array($ext, $raster, true)) {
                $info = @getimagesize($tmpFile);
                if ($info === false) {
                    throw new \RuntimeException('File type not allowed.', 400);
                }
            }
        }

        if ($isSvg) {
            $size = @filesize($tmpFile);
            if ($size === false || $size > 2 * 1024 * 1024) {
                throw new \RuntimeException('File type not allowed.', 400);
            }
            $content = @file_get_contents($tmpFile, false, null, 0, 2 * 1024 * 1024);
            if ($content === false) {
                throw new \RuntimeException('File type not allowed.', 400);
            }
            if (preg_match('/<\s*script|\bon[a-z]+\s*=|javascript\s*:|<\s*foreignObject|<!ENTITY/i', $content)) {
                throw new \RuntimeException('File type not allowed.', 400);
            }
        }
    }
}
