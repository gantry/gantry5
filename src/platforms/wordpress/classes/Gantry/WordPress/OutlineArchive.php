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

namespace Gantry\WordPress;

/**
 * Pure helper for outline export/import archive handling.
 *
 * @package Gantry\WordPress
 */
class OutlineArchive
{
    /** @var callable */
    protected $parse;

    /** @var callable */
    protected $dump;

    /**
     * @param callable|null $parse
     * @param callable|null $dump
     */
    public function __construct(?callable $parse = null, ?callable $dump = null)
    {
        $this->parse = $parse ?: static function ($s) {
            return \Symfony\Component\Yaml\Yaml::parse($s);
        };
        $this->dump = $dump ?: static function ($d) {
            return \Symfony\Component\Yaml\Yaml::dump($d, 10, 2);
        };
    }

    /**
     * @return string[]
     */
    public static function fileExtensions()
    {
        return [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico', 'svg',
            'woff', 'woff2', 'ttf', 'otf', 'eot',
            'mp4', 'webm', 'ogv', 'mov',
        ];
    }

    /**
     * @param mixed $name
     * @return bool
     */
    public static function isSafeEntryName($name)
    {
        if (!\is_string($name) || $name === '') {
            return false;
        }

        if (strpos($name, "\0") !== false) {
            return false;
        }

        $len = \strlen($name);
        for ($i = 0; $i < $len; $i++) {
            if (\ord($name[$i]) < 0x20) {
                return false;
            }
        }

        if ($name[0] === '/' || $name[0] === '\\') {
            return false;
        }

        if (preg_match('/^[a-z]:/i', $name)) {
            return false;
        }

        $normalised = str_replace('\\', '/', $name);
        $segments = explode('/', $normalised);
        foreach ($segments as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \ZipArchive $zip
     * @return void
     */
    public function assertSafeZip(\ZipArchive $zip)
    {
        for ($i = 0, $n = $zip->numFiles; $i < $n; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false || !static::isSafeEntryName($entry)) {
                throw new \RuntimeException('Invalid export file.', 400);
            }
        }
    }

    /**
     * @param string $dir
     * @return void
     */
    public function assertSafeTree($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = $file->getPathname();

            if (is_link($path)) {
                throw new \RuntimeException('Invalid export file.', 400);
            }

            $basename = $file->getFilename();
            if (strpos($basename, "\0") !== false) {
                throw new \RuntimeException('Invalid export file.', 400);
            }

            $len = \strlen($basename);
            for ($i = 0; $i < $len; $i++) {
                if (\ord($basename[$i]) < 0x20) {
                    throw new \RuntimeException('Invalid export file.', 400);
                }
            }
        }
    }

    /**
     * @param array $configDirs
     * @return array
     */
    public function collectOutlines(array $configDirs)
    {
        $names = [];

        foreach ($configDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $entries = @scandir($dir);
            if ($entries === false) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === 'menu' || isset($entry[0]) && $entry[0] === '.') {
                    continue;
                }

                if (!preg_match('/^[a-z0-9_-]+$/i', $entry)) {
                    continue;
                }

                $path = $dir . DIRECTORY_SEPARATOR . $entry;
                if (is_link($path) || !is_dir($path)) {
                    continue;
                }

                $names[$entry] = true;
            }
        }

        $outlines = [];

        foreach (array_keys($names) as $name) {
            $config = [];
            $hasIndexOrLayout = false;

            foreach ($configDirs as $dir) {
                $outlineDir = $dir . DIRECTORY_SEPARATOR . $name;
                if (!is_dir($outlineDir) || is_link($outlineDir)) {
                    continue;
                }

                $files = $this->collectYamlFiles($outlineDir, 3);
                foreach ($files as $relPath => $absPath) {
                    $relKey = preg_replace('/\.ya?ml$/i', '', str_replace('\\', '/', $relPath));
                    if ($relKey === 'particles' || $relKey === 'page') {
                        continue;
                    }

                    if ($relKey === 'index' || $relKey === 'layout') {
                        $hasIndexOrLayout = true;
                    }

                    if (isset($config[$relKey])) {
                        continue;
                    }

                    $raw = @file_get_contents($absPath);
                    if ($raw === false) {
                        continue;
                    }

                    $parsed = \call_user_func($this->parse, $raw);
                    $config[$relKey] = $parsed === null ? [] : $parsed;
                }
            }

            if (!$hasIndexOrLayout) {
                continue;
            }

            $preset = null;
            if (isset($config['layout']['preset']['name'])) {
                $preset = $config['layout']['preset']['name'];
            }

            $outlines[$name] = [
                'title' => $this->title($name),
                'preset' => $preset,
                'config' => $config,
            ];
        }

        uksort($outlines, static function ($a, $b) {
            if ($a === 'default') {
                return -1;
            }
            if ($b === 'default') {
                return 1;
            }

            return strcmp($a, $b);
        });

        return $outlines;
    }

    /**
     * @param array $configDirs
     * @return array
     */
    public function collectMenus(array $configDirs)
    {
        $menus = [];

        foreach ($configDirs as $dir) {
            $menuDir = $dir . DIRECTORY_SEPARATOR . 'menu';
            if (!is_dir($menuDir)) {
                continue;
            }

            $entries = @scandir($menuDir);
            if ($entries === false) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..' || isset($entry[0]) && $entry[0] === '.') {
                    continue;
                }

                if (!preg_match('/^[a-z0-9_-]+\.ya?ml$/i', $entry)) {
                    continue;
                }

                $name = preg_replace('/\.ya?ml$/i', '', $entry);
                if (!preg_match('/^[a-z0-9_-]+$/i', $name)) {
                    continue;
                }

                if (isset($menus[$name])) {
                    continue;
                }

                $path = $menuDir . DIRECTORY_SEPARATOR . $entry;
                if (is_link($path) || !is_file($path)) {
                    continue;
                }

                $raw = @file_get_contents($path);
                if ($raw === false) {
                    continue;
                }

                $parsed = \call_user_func($this->parse, $raw);
                $menus[$name] = [
                    'config' => $parsed === null ? [] : $parsed,
                ];
            }
        }

        return $menus;
    }

    /**
     * @param array $outlines
     * @param array $menus
     * @param callable $exists
     * @return array
     */
    public function collectFileRefs(array $outlines, array $menus, callable $exists)
    {
        $files = [];
        $extensions = static::fileExtensions();
        $pattern = '#^(gantry-media|gantry-theme)://([A-Za-z0-9_./ -]+)$#';

        $walk = function ($value) use (&$walk, &$files, $exists, $extensions, $pattern) {
            if (\is_array($value)) {
                foreach ($value as $v) {
                    $walk($v);
                }

                return;
            }

            if (!\is_string($value) || !preg_match($pattern, $value, $m)) {
                return;
            }

            $stream = $m[1];
            $path = $m[2];

            if (strpos($path, '..') !== false) {
                return;
            }

            $segments = explode('/', str_replace('\\', '/', $path));
            foreach ($segments as $segment) {
                if ($segment === '..' || $segment === '') {
                    return;
                }
            }

            if (isset($path[0]) && $path[0] === '/') {
                return;
            }

            if ($stream === 'gantry-theme' && strpos($path, 'images/') !== 0) {
                return;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!\in_array($ext, $extensions, true)) {
                return;
            }

            $uri = $value;
            if (!\call_user_func($exists, $uri)) {
                return;
            }

            if (!isset($files[$stream])) {
                $files[$stream] = [];
            }

            $files[$stream][$path] = $uri;
        };

        foreach ($outlines as $outline) {
            if (isset($outline['config'])) {
                $walk($outline['config']);
            }
        }

        foreach ($menus as $menu) {
            $walk($menu);
        }

        return $files;
    }

    /**
     * @param array $widgetsExport
     * @param array $sidebarTitles
     * @return array
     */
    public function positionsForExport(array $widgetsExport, array $sidebarTitles)
    {
        $positions = [];

        foreach ($widgetsExport as $sidebar => $items) {
            if (!\is_string($sidebar) || !preg_match('/^[A-Za-z0-9_-]+$/', $sidebar)) {
                continue;
            }

            $ordered = [];
            $i = 0;
            foreach ((array) $items as $data) {
                if (!\is_array($data)) {
                    continue;
                }

                $type = isset($data['type']) ? (string) $data['type'] : 'item';
                $slug = trim(preg_replace('/[^a-z0-9_-]+/', '-', strtolower(str_replace('.', '-', $type))), '-');
                $key = sprintf('%02d-%s', $i + 1, $slug);
                $ordered[$key] = $data;
                $i++;
            }

            $positions[$sidebar] = [
                'title' => isset($sidebarTitles[$sidebar]) ? $sidebarTitles[$sidebar] : $sidebar,
                'items' => $ordered,
            ];
        }

        return $positions;
    }

    /**
     * @param string $positionsDir
     * @return array
     */
    public function readPositions($positionsDir)
    {
        if (!is_dir($positionsDir)) {
            return [];
        }

        $result = [];
        $entries = @scandir($positionsDir);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || isset($entry[0]) && $entry[0] === '.') {
                continue;
            }

            if (!preg_match('/^([A-Za-z0-9_-]+)\.ya?ml$/i', $entry, $m)) {
                continue;
            }

            $sidebar = $m[1];
            $metaPath = $positionsDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($metaPath) || is_link($metaPath)) {
                continue;
            }

            $raw = @file_get_contents($metaPath);
            if ($raw === false) {
                continue;
            }

            $meta = \call_user_func($this->parse, $raw);
            $ordering = [];
            if (\is_array($meta) && isset($meta['ordering']) && \is_array($meta['ordering'])) {
                $ordering = $meta['ordering'];
            }

            $itemDir = $positionsDir . DIRECTORY_SEPARATOR . $sidebar;
            $itemFiles = [];
            if (is_dir($itemDir) && !is_link($itemDir)) {
                $itemEntries = @scandir($itemDir);
                if ($itemEntries !== false) {
                    foreach ($itemEntries as $itemEntry) {
                        if ($itemEntry === '.' || $itemEntry === '..' || isset($itemEntry[0]) && $itemEntry[0] === '.') {
                            continue;
                        }

                        if (!preg_match('/^([A-Za-z0-9_-]+)\.ya?ml$/i', $itemEntry, $im)) {
                            continue;
                        }

                        $module = $im[1];
                        $itemPath = $itemDir . DIRECTORY_SEPARATOR . $itemEntry;
                        if (!is_file($itemPath) || is_link($itemPath)) {
                            continue;
                        }

                        $itemFiles[$module] = $itemPath;
                    }
                }
            }

            $items = [];
            $used = [];

            foreach ($ordering as $module) {
                if (!\is_string($module) || !isset($itemFiles[$module])) {
                    continue;
                }

                $itemRaw = @file_get_contents($itemFiles[$module]);
                if ($itemRaw === false) {
                    continue;
                }

                $data = \call_user_func($this->parse, $itemRaw);
                if (\is_array($data) && isset($data['type']) && \is_string($data['type'])) {
                    $items[] = $data;
                }
                $used[$module] = true;
            }

            $remaining = array_keys($itemFiles);
            sort($remaining);
            foreach ($remaining as $module) {
                if (isset($used[$module])) {
                    continue;
                }

                $itemRaw = @file_get_contents($itemFiles[$module]);
                if ($itemRaw === false) {
                    continue;
                }

                $data = \call_user_func($this->parse, $itemRaw);
                if (\is_array($data) && isset($data['type']) && \is_string($data['type'])) {
                    $items[] = $data;
                }
            }

            $result[$sidebar] = $items;
        }

        return $result;
    }

    /**
     * @param string $sourceDir
     * @param string $targetConfigDir
     * @param bool $includeAssignments
     * @return array
     */
    public function importOutlines($sourceDir, $targetConfigDir, $includeAssignments = true)
    {
        if (!is_dir($sourceDir)) {
            return [];
        }

        $this->assertSafeTree($sourceDir);

        $prepared = [];
        $entries = @scandir($sourceDir);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'menu' || isset($entry[0]) && $entry[0] === '.') {
                continue;
            }

            if (!preg_match('/^[a-z0-9_-]+$/i', $entry)) {
                continue;
            }

            $outlineDir = $sourceDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($outlineDir) || is_link($outlineDir)) {
                continue;
            }

            $files = $this->collectYamlFiles($outlineDir, 3, true);
            $outlineFiles = [];

            foreach ($files as $relPath => $absPath) {
                $relNorm = str_replace('\\', '/', $relPath);

                if (!$includeAssignments && strcasecmp($relNorm, 'assignments.yaml') === 0) {
                    continue;
                }

                $raw = @file_get_contents($absPath);
                if ($raw === false) {
                    throw new \RuntimeException('Invalid export file.', 400);
                }

                try {
                    \call_user_func($this->parse, $raw);
                } catch (\Exception $e) {
                    throw new \RuntimeException('Invalid export file.', 400);
                }

                $outlineFiles[$relNorm] = $raw;
            }

            if ($outlineFiles) {
                $prepared[$entry] = $outlineFiles;
            }
        }

        // Nothing to import: leave the existing outlines alone.
        if (!$prepared) {
            return [];
        }

        if (!is_dir($targetConfigDir)) {
            if (!@mkdir($targetConfigDir, 0755, true) && !is_dir($targetConfigDir)) {
                throw new \RuntimeException('Unable to locate configuration folder.', 500);
            }
        }

        $existing = @scandir($targetConfigDir);
        if ($existing !== false) {
            foreach ($existing as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === 'menu' || isset($entry[0]) && $entry[0] === '.') {
                    continue;
                }

                $path = $targetConfigDir . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($path) || is_link($path)) {
                    $this->deleteRecursive($path);
                }
            }
        }

        foreach ($prepared as $name => $files) {
            $outlineTarget = $targetConfigDir . DIRECTORY_SEPARATOR . $name;
            foreach ($files as $relPath => $raw) {
                $dest = $outlineTarget . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                $parent = \dirname($dest);
                if (!is_dir($parent)) {
                    if (!@mkdir($parent, 0755, true) && !is_dir($parent)) {
                        throw new \RuntimeException('Unable to locate configuration folder.', 500);
                    }
                }
                file_put_contents($dest, $raw);
            }
        }

        $names = array_keys($prepared);
        usort($names, static function ($a, $b) {
            if ($a === 'default') {
                return -1;
            }
            if ($b === 'default') {
                return 1;
            }

            return strcmp($a, $b);
        });

        return $names;
    }

    /**
     * @param string $sourceDir
     * @param string $targetConfigDir
     * @return array
     */
    public function importMenus($sourceDir, $targetConfigDir)
    {
        if (!is_dir($sourceDir)) {
            return [];
        }

        $names = [];
        $entries = @scandir($sourceDir);
        if ($entries === false) {
            return [];
        }

        $menuTarget = $targetConfigDir . DIRECTORY_SEPARATOR . 'menu';

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || isset($entry[0]) && $entry[0] === '.') {
                continue;
            }

            if (!preg_match('/^([a-z0-9_-]+)\.ya?ml$/i', $entry, $m)) {
                continue;
            }

            $name = $m[1];
            $path = $sourceDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path) || is_link($path)) {
                continue;
            }

            $raw = @file_get_contents($path);
            if ($raw === false) {
                continue;
            }

            try {
                $data = \call_user_func($this->parse, $raw);
            } catch (\Exception $e) {
                throw new \RuntimeException('Invalid export file.', 400);
            }

            if (!\is_array($data) || !isset($data['config']) || !\is_array($data['config'])) {
                continue;
            }

            if (!is_dir($menuTarget)) {
                if (!@mkdir($menuTarget, 0755, true) && !is_dir($menuTarget)) {
                    throw new \RuntimeException('Unable to locate configuration folder.', 500);
                }
            }

            $dumped = \call_user_func($this->dump, $data['config']);
            file_put_contents($menuTarget . DIRECTORY_SEPARATOR . $name . '.yaml', $dumped);
            $names[] = $name;
        }

        return $names;
    }

    /**
     * @param string $sourceDir
     * @param callable $resolveTarget
     * @return int
     */
    public function importFiles($sourceDir, callable $resolveTarget)
    {
        if (!is_dir($sourceDir)) {
            return 0;
        }

        $this->assertSafeTree($sourceDir);

        $copied = 0;
        $extensions = static::fileExtensions();
        $streams = ['gantry-media', 'gantry-theme'];

        foreach ($streams as $stream) {
            $streamDir = $sourceDir . DIRECTORY_SEPARATOR . $stream;
            if (!is_dir($streamDir) || is_link($streamDir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($streamDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile() || is_link($file->getPathname())) {
                    continue;
                }

                $full = $file->getPathname();
                $rel = substr($full, \strlen($streamDir) + 1);
                $rel = str_replace('\\', '/', $rel);

                $segments = explode('/', $rel);
                $safe = true;
                foreach ($segments as $segment) {
                    if ($segment === '' || isset($segment[0]) && $segment[0] === '.'
                        || !preg_match('/^[A-Za-z0-9_. -]+$/', $segment)
                    ) {
                        $safe = false;
                        break;
                    }
                }

                if (!$safe) {
                    continue;
                }

                if ($stream === 'gantry-theme' && strpos($rel, 'images/') !== 0) {
                    continue;
                }

                $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
                if (!\in_array($ext, $extensions, true)) {
                    continue;
                }

                if ($ext === 'svg') {
                    $content = @file_get_contents($full);
                    if ($content === false) {
                        continue;
                    }
                    if (preg_match('/<\s*script|\bon[a-z]+\s*=|javascript\s*:|<\s*foreignObject|<!ENTITY/i', $content)) {
                        continue;
                    }
                }

                $uri = $stream . '://' . $rel;
                $target = \call_user_func($resolveTarget, $uri);
                if (!\is_string($target) || $target === '') {
                    continue;
                }

                $parent = \dirname($target);
                if (!is_dir($parent)) {
                    if (!@mkdir($parent, 0755, true) && !is_dir($parent)) {
                        continue;
                    }
                }

                if (@copy($full, $target)) {
                    $copied++;
                }
            }
        }

        return $copied;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function title($name)
    {
        if ($name === 'default') {
            return 'Default';
        }

        return ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $name)));
    }

    /**
     * Collect yaml files under a directory.
     *
     * @param string $dir
     * @param int $maxDepth
     * @param bool $strictSegments
     * @return array relPath => absPath
     */
    protected function collectYamlFiles($dir, $maxDepth = 3, $strictSegments = false)
    {
        $result = [];
        $this->walkYaml($dir, '', 0, $maxDepth, $strictSegments, $result);

        return $result;
    }

    /**
     * @param string $dir
     * @param string $prefix
     * @param int $depth
     * @param int $maxDepth
     * @param bool $strictSegments
     * @param array $result
     * @return void
     */
    protected function walkYaml($dir, $prefix, $depth, $maxDepth, $strictSegments, array &$result)
    {
        if ($depth > $maxDepth) {
            return;
        }

        $entries = @scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || isset($entry[0]) && $entry[0] === '.') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_link($path)) {
                continue;
            }

            $rel = $prefix === '' ? $entry : $prefix . '/' . $entry;

            if (is_dir($path)) {
                if ($strictSegments && !preg_match('/^[A-Za-z0-9_.-]+$/', $entry)) {
                    continue;
                }
                if ($depth + 1 <= $maxDepth) {
                    $this->walkYaml($path, $rel, $depth + 1, $maxDepth, $strictSegments, $result);
                }
                continue;
            }

            if (!is_file($path)) {
                continue;
            }

            if (!preg_match('/\.ya?ml$/i', $entry)) {
                continue;
            }

            if ($strictSegments) {
                $base = preg_replace('/\.ya?ml$/i', '', $entry);
                if (!preg_match('/^[A-Za-z0-9_.-]+$/', $base)) {
                    continue;
                }
                $parts = explode('/', $rel);
                $ok = true;
                foreach ($parts as $i => $part) {
                    if ($i === \count($parts) - 1) {
                        $part = preg_replace('/\.ya?ml$/i', '', $part);
                    }
                    if (!preg_match('/^[A-Za-z0-9_.-]+$/', $part)) {
                        $ok = false;
                        break;
                    }
                }
                if (!$ok) {
                    continue;
                }
            }

            $result[$rel] = $path;
        }
    }

    /**
     * @param string $path
     * @return void
     */
    protected function deleteRecursive($path)
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
                $this->deleteRecursive($path . DIRECTORY_SEPARATOR . $entry);
            }
        }

        @rmdir($path);
    }
}
