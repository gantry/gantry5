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
class CompiledTheme extends CompiledBase
{
    /**
     * @var int Version number for the compiled file.
     */
    public $version = 1;

    /**
     * @var Config  Configuration object.
     */
    protected $object;

    /**
     * @var callable  Blueprints loader.
     */
    protected $callable;

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
     * Create configuration object.
     *
     * @param  array  $data
     */
    protected function createObject(array $data = [])
    {
        $this->object = new Config($data, $this->callable);
    }

    /**
     * Finalize configuration object.
     */
    protected function finalizeObject()
    {
        $theme = $this->object->get('theme');

        if (isset($theme['details'])) {
            $this->object->undef('theme');

            // Convert old file format into the new one.
            $this->object->def('theme.name', (string) isset($theme['details']['name']) ? $theme['details']['name'] : null);
            $this->object->def('theme.version', (string) isset($theme['details']['version']) ? $theme['details']['version'] : null);
            $this->object->def('theme.date', (string) isset($theme['details']['date']) ? $theme['details']['date'] : null);
            $this->object->def('theme.gantry', (array) isset($theme['configuration']['gantry']) ? $theme['configuration']['gantry'] : null);
            $this->object->def('theme.setup', (array) isset($theme['configuration']['theme']) ? $theme['configuration']['theme'] : null);
            $this->object->def('dependencies', (array) isset($theme['configuration']['dependencies']) ? $theme['configuration']['dependencies'] : null);

            unset($theme['details']['version']);
            unset($theme['details']['date']);
            unset($theme['configuration']['gantry']);
            unset($theme['configuration']['theme']);
            unset($theme['configuration']['dependencies']);

            foreach ($theme as $key => $value) {
                $this->object->def($key, $value);
            }
        }
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
