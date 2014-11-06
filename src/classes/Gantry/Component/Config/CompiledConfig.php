<?php
namespace Gantry\Component\Config;

use Grav\Common\File\CompiledYamlFile;

/**
 * The Compiled Configuration class.
 */
class CompiledConfig extends CompiledBase
{
    /**
     * @var Config  Configuration object.
     */
    protected $object;

    /**
     * @var callable  Blueprints loader.
     */
    protected $callable;

    /**
     * @param  array  $files  List of files as returned from ConfigFileFinder class.
     * @param  callable  $blueprints  Lazy load function for blueprints.
     * @throws \BadMethodCallException
     */
    public function __construct(array $files, callable $blueprints = null)
    {
        if (!$blueprints) {
            throw new \BadMethodCallException('You cannot instantiate configuration without blueprints');
        }
        $this->files = $files;
        $this->callable = $blueprints;

        $name = md5(json_encode(array_keys($files)));

        $this->filename = CACHE_DIR . 'compiled/config/' . $name . '.php';
    }

    /**
     * Create configuration object.
     *
     * @param  array  $data
     */
    protected function createObject(array $data = [])
    {
        $this->object = new Config($data, $this->callable);
    }

    /**
     * Load single configuration file and append it to the correct position.
     *
     * @param  string  $name  Name of the position.
     * @param  string  $filename  File to be loaded.
     */
    protected function loadFile($name, $filename)
    {
        $file = CompiledYamlFile::instance($filename);
        $this->object->join($name, $file->content(), '/');
    }
}
