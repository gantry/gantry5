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

use Gantry\Component\File\CompiledYamlFile;

/**
 * The Compiled Configuration class.
 */
class CompiledConfig extends CompiledBase
{
    /**
     * @var int Version number for the compiled file.
     */
    public $version = 2;

    /**
     * @var Config  Configuration object.
     */
    protected $object;

    /**
     * @var callable  Blueprints loader.
     */
    protected $callable;

    /**
     * @var bool
     */
    protected $withDefaults;

    /**
     * Get filename for the compiled PHP file.
     *
     * @param string $name
     * @return $this
     */
    public function name($name = null)
    {
        if (!$this->name) {
            $this->name = $name ?: md5(json_encode(array_keys($this->files)) . (int) $this->withDefaults);
        }

        return $this;
    }

    /**
     * Set blueprints for the configuration.
     *
     * @param callable $blueprints
     * @return $this
     */
    public function setBlueprints(callable $blueprints)
    {
        $this->callable = $blueprints;

        return $this;
    }

    /**
     * @param bool $withDefaults
     * @return mixed
     */
    public function load($withDefaults = false)
    {
        $this->withDefaults = $withDefaults;

        return parent::load();
    }

    /**
     * Create configuration object.
     *
     * @param  array  $data
     */
    protected function createObject(array $data = [])
    {
        if ($this->withDefaults && empty($data) && is_callable($this->callable)) {
            $blueprints = $this->callable;
            $data = $blueprints()->getDefaults();
        }

        $this->object = new Config($data, $this->callable);
    }

    /**
     * Finalize configuration object.
     */
    protected function finalizeObject()
    {
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
        $file->free();
    }
}
