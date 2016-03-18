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
     * @param $name
     * @param $position
     */
    public function __construct($name, $position)
    {
        $this->name = $name;
        $this->position = $position;
        $this->load();
    }

    /**
     * Save module.
     *
     * @return $this
     */
    public function save()
    {
        $file = $this->file(true);
        $file->save($this->toArray());

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

    protected function load()
    {
        $file = $this->file();
        $module = $file->content();
        $file->free();

        if (isset($module['assignments'])) {
            $assignments = $module['assignments'];
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

        $this->items = $module;
    }

    protected function file($save = false)
    {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        return CompiledYamlFile::instance($locator->findResource("gantry-positions://{$this->position}/{$this->name}.yaml", true, $save));
    }
}
