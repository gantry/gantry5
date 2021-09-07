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

namespace Gantry\Component\Config;

use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\File\PhpFile;

/**
 * The Compiled base class.
 */
abstract class CompiledBase
{
    /**
     * @var int Version number for the compiled file.
     */
    public $version = 1;

    /**
     * @var string  Filename (base name) of the compiled configuration.
     */
    public $name;

    /**
     * @var string|bool  Configuration checksum.
     */
    public $checksum;

    /**
     * @var string Cache folder to be used.
     */
    protected $cacheFolder;

    /**
     * @var array  List of files to load.
     */
    protected $files;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var mixed  Configuration object.
     */
    protected $object;

    /**
     * @param  string $cacheFolder  Cache folder to be used.
     * @param  array  $files  List of files as returned from ConfigFileFinder class.
     * @param string $path  Base path for the file list.
     * @throws \BadMethodCallException
     */
    public function __construct($cacheFolder, array $files, $path = GANTRY5_ROOT)
    {
        if (!$cacheFolder) {
            throw new \BadMethodCallException('Cache folder not defined.');
        }

        $this->cacheFolder = $cacheFolder;
        $this->files = $files;
        $this->path = $path ? rtrim($path, '\\/') . '/' : '';
    }

    /**
     * Get filename for the compiled PHP file.
     *
     * @param string $name
     * @return $this
     */
    public function name($name = null)
    {
        if (!$this->name) {
            $this->name = $name ?: md5(json_encode(array_keys($this->files)));
        }

        return $this;
    }

    /**
     * Function gets called when cached configuration is saved.
     */
    public function modified() {}

    /**
     * Load the configuration.
     *
     * @param mixed $data
     * @return mixed
     */
    public function load($data = [])
    {
        if (!$this->object) {
            $data = is_array($data) ? $data : [];
            $filename = $this->createFilename();
            if (!$this->loadCompiledFile($filename) && $this->loadFiles($data)) {
                $this->saveCompiledFile($filename);
            }
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
            $this->checksum = md5(json_encode($this->files) . $this->version);
        }

        return $this->checksum;
    }

    protected function createFilename()
    {
        return "{$this->cacheFolder}/{$this->name()->name}.php";
    }

    /**
     * Create configuration object.
     *
     * @param  array  $data
     */
    abstract protected function createObject(array $data);

    /**
     * Finalize configuration object.
     */
    abstract protected function finalizeObject();

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
     * @param array $data
     * @return bool
     * @internal
     */
    protected function loadFiles(array $data)
    {
        $this->createObject($data);

        $list = array_reverse($this->files);
        foreach ($list as $files) {
            foreach ($files as $name => $item) {
                $this->loadFile($name, $this->path . $item['file']);
            }
        }

        $this->finalizeObject();

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
        $gantry = Gantry::instance();

        /** @var Config $global */
        $global = $gantry['global'];

        if (!$global->get('compile_yaml', 1)) {
            return false;
        }

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

        $this->finalizeObject();

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
        $gantry = Gantry::instance();

        /** @var Config $global */
        $global = $gantry['global'];

        if (!$global->get('compile_yaml', 1)) {
            return;
        }

        $file = PhpFile::instance($filename);

        // Attempt to lock the file for writing.
        try {
            $file->lock(false);
        } catch (\Exception $e) {
            // Another process has locked the file; we will check this in a bit.
        }

        if ($file->locked() === false) {
            // File was already locked by another process.
            return;
        }

        $cache = [
            '@class' => get_class($this),
            'timestamp' => time(),
            'checksum' => $this->checksum(),
            'files' => $this->files,
            'data' => $this->getState()
        ];

        $file->save($cache);
        $file->unlock();
        $file->free();

        $this->modified();
    }

    protected function getState()
    {
        return $this->object->toArray();
    }
}
