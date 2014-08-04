<?php
namespace Gantry\Base;

use Gantry\Data\Blueprints;
use Gantry\Data\Data;
use Gantry\Filesystem\File;
use Gantry\Filesystem\Folder;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */
class Config extends Data
{
    /**
     * @var string Configuration location in the disk.
     */
    public $filename;

    /**
     * @var string Path to YAML configuration.
     */
    public $path;

    /**
     * @var string MD5 from the files.
     */
    public $key;

    /**
     * @var array Configuration file list.
     */
    public $files = array();

    /**
     * @var bool Flag to tell if configuration needs to be saved.
     */
    public $updated = false;

    /**
     * Constructor.
     */
    public function __construct($filename, $path)
    {
        $this->filename = $filename;
        $this->path = (string) $path;

        $this->reload(false);
    }

    /**
     * Force reload of the configuration from the disk.
     *
     * @param bool $force
     * @return $this
     */
    public function reload($force = true)
    {
        // Build file map.
        $files = $this->build();
        $key = md5(serialize($files) . GANTRY5_VERSION);

        if ($force || $key != $this->key) {
            // First take non-blocking lock to the file.
            File\Config::instance($this->filename)->lock(false);

            // Reset configuration.
            $this->items = array();
            $this->files = array();
            $this->init($files);
            $this->key = $key;
        }

        return $this;
    }

    /**
     * Save configuration into file.
     *
     * Note: Only saves the file if updated flag is set!
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function save()
    {
        // If configuration was updated, store it as cached version.
        try {
            $file = File\Config::instance($this->filename);

            // Only save configuration file if it wasn't locked. Also invalidate opcache after saving.
            // This prevents us from saving the file multiple times in a row and gives faster recovery.
            if ($file->locked() !== false) {
                $file->save($this);
                $file->unlock();
            }
            $this->updated = false;
        } catch (\Exception $e) {
            // TODO: do not require saving to succeed, but display some kind of error anyway.
            throw new \RuntimeException('Writing configuration to cache folder failed.', 500, $e);
        }

        return $this;
    }

    /**
     * Gets configuration instance.
     *
     * @param  string  $filename
     * @return Config
     */
    public static function instance($filename, $path)
    {
        // Load cached version if available..
        if (file_exists($filename)) {
            require_once $filename;

            if (class_exists('\Gantry\Config')) {
                $instance = new \Gantry\Config($filename, $path);
            }
        }

        // Or initialize new configuration object..
        if (!isset($instance)) {
            $instance = new static($filename, $path);
        }

        // If configuration was updated, store it as cached version.
        if ($instance->updated) {
            $instance->save();
        }

        return $instance;
    }

    /**
     * Convert configuration into an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array('key' => $this->key, 'files' => $this->files, 'items' => $this->items);
    }

    /**
     * Initialize object by loading all the configuration files.
     *
     * @param array $files
     */
    protected function init(array $files)
    {
        $this->updated = true;

        // Combine all configuration files into one larger lookup table (only keys matter).
        $allFiles = $files['theme'];

        // Then sort the files to have all parent nodes first.
        // This is to make sure that child nodes override parents content.
        uksort(
            $allFiles,
            function($a, $b) {
                $diff = substr_count($a, '/') - substr_count($b, '/');
                return $diff ? $diff : strcmp($a, $b);
            }
        );

        $blueprints = new Blueprints($this->path . '/blueprints/config');

        $items = array();
        foreach ($allFiles as $name => $dummy) {
            $lookup = array(
                'theme' => $this->path . '/config/' . $name . '.yaml',
            );
            $blueprint = $blueprints->get($name);

            $data = new Data(array(), $blueprint);
            foreach ($lookup as $path) {
                if (is_file($path)) {
                    $data->merge(File\Yaml::instance($path)->content());
                }
            }

            // Find the current sub-tree location.
            $current = &$items;
            $parts = explode('/', $name);
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = array();
                }
                $current = &$current[$part];
            }

            // Handle both updated and deleted configuration files.
            $current = $data->toArray();
        }

        $this->items = $items;
        $this->files = $files;
    }

    /**
     * Build a list of configuration files with their timestamps. Used for loading settings and caching them.
     *
     * @return array
     * @internal
     */
    protected function build()
    {
        // Find all system and user configuration files.
        $options = array(
            'compare' => 'Filename',
            'pattern' => '|\.yaml$|',
            'filters' => array('key' => '|\.yaml$|'),
            'key' => 'SubPathname',
            'value' => 'MTime'
        );

        $user = Folder::all($this->path . '/config', $options);

        return array('theme' => $user);
    }
}
