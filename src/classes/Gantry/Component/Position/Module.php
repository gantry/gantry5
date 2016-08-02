<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Position;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccessWithGetters;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Module implements \ArrayAccess
{
    use NestedArrayAccessWithGetters, Export;

    public $name;
    public $position;
    public $assigned;
    protected $items;

    /**
     * Module constructor.
     *
     * @param string $name
     * @param string $position
     * @param array $data
     */
    public function __construct($name, $position, array $data = null)
    {
        $this->name = $name;
        $this->position = $position;

        if ($data) {
            $this->init($data);
        } else {
            $this->load();
        }
    }

    public function move($position, $id = null)
    {
        $this->items['position'] = $this->position;
        $this->position = $position;

        if ($id !== null) {
            $this->items['id'] = $this->name;
            $this->name = $id;
        }
    }

    /**
     * Save module.
     *
     * @return $this
     */
    public function save()
    {
        $items = $this->toArray();
        unset($items['position'], $items['id']);

        $file = $this->file(true);
        $file->save($items);

        return $this;
    }

    /**
     * Delete module.
     *
     * @return $this
     */
    public function delete()
    {
        $file = $this->file(true);
        $file->delete();

        return $this;
    }

    public function toArray()
    {
        return  ['position' => $this->position, 'id' => $this->name] + $this->items;
    }

    protected function load()
    {
        $file = $this->file();
        $this->init($file->content());
        $file->free();
    }

    protected function init($data)
    {
        $this->items = $data;

        if (isset($this->items['assignments'])) {
            $assignments = $this->items['assignments'];
            if (is_array($assignments)) {
                $this->assigned = 'some';
            } elseif ($assignments !== 'all') {
                $this->assigned = 'none';
            } else {
                $this->assigned = 'all';
            }
        } else {
            $this->assigned = 'all';
        }
    }

    protected function file($save = false)
    {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        return CompiledYamlFile::instance($locator->findResource("gantry-positions://{$this->position}/{$this->name}.yaml", true, $save));
    }
}
