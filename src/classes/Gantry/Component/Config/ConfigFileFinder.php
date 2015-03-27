<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Config;

use Gantry\Component\Filesystem\Folder;

/**
 * The Configuration & Blueprints Finder class.
 */
class ConfigFileFinder
{
    /**
     * Return all locations for all the files with a timestamp.
     *
     * @param  array  $paths    List of folders to look from.
     * @param  string $pattern  Pattern to match the file. Pattern will also be removed from the key.
     * @param  int    $levels   Maximum number of recursive directories.
     * @return array
     */
    public function locateFiles(array $paths, $pattern = '|\.yaml$|', $levels = -1)
    {
        $list = [];
        foreach ($paths as $folder) {
            $list += $this->detectRecursive($folder, $pattern, $levels);
        }
        return $list;
    }

    /**
     * Return all locations for all the files with a timestamp.
     *
     * @param  array  $paths    List of folders to look from.
     * @param  string $pattern  Pattern to match the file. Pattern will also be removed from the key.
     * @param  int    $levels   Maximum number of recursive directories.
     * @return array
     */
    public function getFiles(array $paths, $pattern = '|\.yaml$|', $levels = -1)
    {
        $list = [];
        foreach ($paths as $folder) {
            $path = trim(Folder::getRelativePath($folder), '/');

            $files = $this->detectRecursive($folder, $pattern, $levels);

            $list += $files[trim($path, '/')];
        }
        return $list;
    }

    /**
     * Return all paths for all the files with a timestamp.
     *
     * @param  array  $paths    List of folders to look from.
     * @param  string $pattern  Pattern to match the file. Pattern will also be removed from the key.
     * @param  int    $levels   Maximum number of recursive directories.
     * @return array
     */
    public function listFiles(array $paths, $pattern = '|\.yaml$|', $levels = -1)
    {
        $list = [];
        foreach ($paths as $folder) {
            $list = array_merge_recursive($list, $this->detectAll($folder, $pattern, $levels));
        }
        return $list;
    }

    /**
     * Return all existing locations for a single file with a timestamp.
     *
     * @param  array  $paths   Filesystem paths to look up from.
     * @param  string $name    Configuration file to be located.
     * @param  string $ext     File extension (optional, defaults to .yaml).
     * @return array
     */
    public function locateFile(array $paths, $name, $ext = '.yaml')
    {
        $filename = preg_replace('|[.\/]+|', '/', $name) . $ext;

        $list = [];
        foreach ($paths as $folder) {
            $path = trim(Folder::getRelativePath($folder), '/');

            if (is_file("{$folder}/{$filename}")) {
                $modified = filemtime("{$folder}/{$filename}");
            } else {
                $modified = 0;
            }
            $list[$path] = [$name => ['file' => "{$path}/{$filename}", 'modified' => $modified]];
        }

        return $list;
    }

    /**
     * Detects all plugins with a configuration file and returns them with last modification time.
     *
     * @param  string $folder   Location to look up from.
     * @param  string $pattern  Pattern to match the file. Pattern will also be removed from the key.
     * @param  int    $levels   Maximum number of recursive directories.
     * @return array
     * @internal
     */
    protected function detectRecursive($folder, $pattern, $levels)
    {
        $path = trim(Folder::getRelativePath($folder), '/');

        if (is_dir($folder)) {
            // Find all system and user configuration files.
            $options = [
                'levels'  => $levels,
                'compare' => 'Filename',
                'pattern' => $pattern,
                'filters' => [
                    'key' => $pattern,
                    'value' => function (\RecursiveDirectoryIterator $file) use ($path) {
                        return ['file' => "{$path}/{$file->getSubPathname()}", 'modified' => $file->getMTime()];
                    }
                ],
                'key' => 'SubPathname'
            ];

            $list = Folder::all($folder, $options);

            ksort($list);
        } else {
            $list = [];
        }

        return [$path => $list];
    }

    /**
     * Detects all plugins with a configuration file and returns them with last modification time.
     *
     * @param  string $folder   Location to look up from.
     * @param  string $pattern  Pattern to match the file. Pattern will also be removed from the key.
     * @param  int    $levels   Maximum number of recursive directories.
     * @return array
     * @internal
     */
    protected function detectAll($folder, $pattern, $levels)
    {
        $path = trim(Folder::getRelativePath($folder), '/');

        if (is_dir($folder)) {
            // Find all system and user configuration files.
            $options = [
                'levels'  => $levels,
                'compare' => 'Filename',
                'pattern' => $pattern,
                'filters' => [
                    'key' => $pattern,
                    'value' => function (\RecursiveDirectoryIterator $file) use ($path) {
                        return ["{$path}/{$file->getSubPathname()}" => $file->getMTime()];
                    }
                ],
                'key' => 'SubPathname'
            ];

            $list = Folder::all($folder, $options);

            ksort($list);
        } else {
            $list = [];
        }

        return $list;
    }
}
