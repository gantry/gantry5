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

use Gantry\Component\File\CompiledYamlFile;

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
     * @param  string $cacheFolder  Cache folder to be used.
     * @param  array  $files  List of files as returned from ConfigFileFinder class.
     * @param  callable  $blueprints  Lazy load function for blueprints.
     * @throws \BadMethodCallException
     */
    public function __construct($cacheFolder, array $files, callable $blueprints = null)
    {
        if (!$blueprints) {
            throw new \BadMethodCallException('You cannot instantiate configuration without blueprints.');
        }
        parent::__construct($cacheFolder, $files);

        $this->callable = $blueprints;
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
