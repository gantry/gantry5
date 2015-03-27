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

use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\PhpFile;

/**
 * The Compiled base class.
 */
abstract class CompiledBase
{
    /**
     * @var string|bool  Configuration checksum.
     */
    public $checksum;

    /**
     * @var string Cache folder to be used.
     */
    protected $cacheFolder;

    /**
     * @var string  Filename
     */
    protected $filename;

    /**
     * @var array  List of files to load.
     */
    protected $files;

    /**
     * @var mixed  Configuration object.
     */
    protected $object;

    /**
     * @param  string $cacheFolder  Cache folder to be used.
     * @param  array  $files  List of files as returned from ConfigFileFinder class.
     * @throws \BadMethodCallException
     */
    public function __construct($cacheFolder, array $files)
    {
        if (!$cacheFolder) {
            throw new \BadMethodCallException('Cache folder not defined.');
        }

        $this->cacheFolder = $cacheFolder;
        $this->files = $files;
    }

    /**
     * Load the configuration.
     *
     * @return mixed
     */
    public function load()
    {
        if ($this->object) {
            return $this->object;
        }

        $filename = $this->filename();
        if (!$this->loadCompiledFile($filename) && $this->loadFiles()) {
            $this->saveCompiledFile($filename);
        }

        return $this->object;
    }

    /**
     * Returns checksum from the configuration files.
     *
     * You can set $this->checksum = false to disable this check.
     *
     * @return bool|string
     */
    public function checksum()
    {
        if (!isset($this->checksum)) {
            $this->checksum = md5(json_encode($this->files));
        }

        return $this->checksum;
    }

    /**
     * Get filename for the compiled PHP file.
     *
     * @return string
     */
    protected function filename()
    {
        $name = md5(json_encode(array_keys($this->files)));

        return "{$this->cacheFolder}/{$name}.php";
    }

    /**
     * Create configuration object.
     *
     * @param  array  $data
     */
    abstract protected function createObject(array $data = []);

    /**
     * Load single configuration file and append it to the correct position.
     *
     * @param  string  $name  Name of the position.
     * @param  string  $filename  File to be loaded.
     */
    abstract protected function loadFile($name, $filename);

    /**
     * Load and join all configuration files.
     *
     * @return bool
     * @internal
     */
    protected function loadFiles()
    {
        $this->createObject();

        foreach (array_reverse($this->files) as $files) {
            foreach ($files as $name => $item) {
                $this->loadFile($name, GANTRY5_ROOT . '/' . $item['file']);
            }
        }

        return true;
    }

    /**
     * Load compiled file.
     *
     * @param  string  $filename
     * @return bool
     * @internal
     */
    protected function loadCompiledFile($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }

        $cache = include $filename;
        if (
            !is_array($cache)
            || !isset($cache['checksum'])
            || !isset($cache['data'])
            || !isset($cache['@class'])
            || $cache['@class'] != get_class($this)
        ) {
            return false;
        }

        // Load real file if cache isn't up to date (or is invalid).
        if ($cache['checksum'] !== $this->checksum()) {
            return false;
        }

        $this->createObject($cache['data']);

        return true;
    }

    /**
     * Save compiled file.
     *
     * @param  string  $filename
     * @throws \RuntimeException
     * @internal
     */
    protected function saveCompiledFile($filename)
    {
        $file = PhpFile::instance($filename);

        // Attempt to lock the file for writing.
        $file->lock(false);

        if ($file->locked() === false) {
            // File was already locked by another process.
            return;
        }

        $cache = [
            '@class' => get_class($this),
            'timestamp' => time(),
            'checksum' => $this->checksum(),
            'files' => $this->files,
            'data' => $this->object->toArray()
        ];

        $file->save($cache);
        $file->unlock();
    }
}
