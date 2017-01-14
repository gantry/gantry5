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

namespace Gantry\Component\File;

use RocketTheme\Toolbox\File\PhpFile;

/**
 * Class CompiledFile
 * @package Grav\Common\File
 *
 * @property string $filename
 * @property string $extension
 * @property string $raw
 * @property array|string $content
 */
trait CompiledFile
{
    protected $cachePath;
    protected $caching = true;

    /**
     * @param string $path
     * @return $this
     */
    public function setCachePath($path)
    {
        $this->cachePath = $path;

        return $this;
    }

    public function caching($enabled = null)
    {
        if (null !== $enabled) {
            $this->caching = (bool) $enabled;
        }

        return $this->caching;
    }

    /**
     * Get/set parsed file contents.
     *
     * @param mixed $var
     * @return string
     * @throws \BadMethodCallException
     */
    public function content($var = null)
    {
        if (!$this->cachePath) {
            throw new \BadMethodCallException("Cache path not defined for compiled file ({$this->filename})!");
        }

        try {
            // If nothing has been loaded, attempt to get pre-compiled version of the file first.
            if ($var === null && $this->raw === null && $this->content === null) {
                $modified = $this->modified();

                if (!$modified || !$this->caching) {
                    return $this->decode($this->raw());
                }

                $key = md5($this->filename);
                $file = PhpFile::instance($this->cachePath . "/{$key}{$this->extension}.php");

                $class = get_class($this);

                $cache = $file->exists() ? $file->content() : null;

                // Load real file if cache isn't up to date (or is invalid).
                if (!isset($cache['@class'])
                    || $cache['@class'] != $class
                    || $cache['modified'] != $modified
                    || $cache['filename'] != $this->filename
                ) {
                    // Attempt to lock the file for writing.
                    try {
                        $file->lock(false);
                    } catch (\Exception $e) {
                        // Another process has locked the file; we will check this in a bit.
                    }

                    // Decode RAW file into compiled array.
                    $data = $this->decode($this->raw());
                    $cache = [
                        '@class' => $class,
                        'filename' => $this->filename,
                        'modified' => $modified,
                        'data' => $data
                    ];

                    // If compiled file wasn't already locked by another process, save it.
                    if ($file->locked() !== false) {
                        $file->save($cache);
                        $file->unlock();

                        // Compile cached file into bytecode cache
                        if (function_exists('opcache_invalidate')) {
                            // Silence error in case if `opcache.restrict_api` directive is set.
                            @opcache_invalidate($file->filename(), true);
                        } elseif (function_exists('apc_compile_file')) {
                            // PHP 5.4
                            @apc_compile_file($file->filename());
                        }
                    }
                }
                $file->free();

                $this->content = $cache['data'];
            }

        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to read %s: %s', basename($this->filename), $e->getMessage()), 500, $e);
        }

        return parent::content($var);
    }
}
