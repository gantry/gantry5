<?php
namespace Gantry\Component\Config;

use Grav\Common\File\CompiledYamlFile;
use RocketTheme\Toolbox\Blueprints\Blueprints;

/**
 * The Compiled Blueprints class.
 */
class CompiledBlueprints extends CompiledBase
{
    /**
     * @var Blueprints  Blueprints object.
     */
    protected $object;

    /**
     * @param  array  $files  List of files as returned from ConfigFileFinder class.
     */
    public function __construct(array $files)
    {
        $this->files = $files;

        $name = md5(json_encode(array_keys($files)));

        $this->filename = CACHE_DIR . 'compiled/blueprints/' . $name . '.php';
    }

    /**
     * Create configuration object.
     *
     * @param array  $data
     */
    protected function createObject(array $data = [])
    {
        $this->object = new Blueprints($data);
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
        $this->object->embed($name, $file->content(), '/');
    }
}
