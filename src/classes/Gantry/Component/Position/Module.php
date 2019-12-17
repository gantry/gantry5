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
    public function __construct($name, $position = null, array $data = null)
    {
        $this->name = $name;
        $this->position = $position;

        if ($data) {
            $this->init($data);
        } else {
            $this->load();
        }
    }

    public function update(array $data)
    {
        $this->init($data);

        return $this;
    }

    /**
     * Save module.
     *
     * @param string $position
     * @param string $name
     * @return $this
     */
    public function save($name = null, $position = null)
    {
        $this->name = $name ?: $this->name;
        $this->position = $position ?: $this->position;

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
        if ($file->exists()) {
            $file->delete();
        }

        return $this;
    }

    /**
     * Return true if module exists.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->name ? $this->file()->exists() : false;
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
        unset($data['id'], $data['position']);

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
        $position = $this->position ?: '_unassigned_';

        $this->name = $this->name ?: ($save ? $this->findFreeName() : null);
        $name = $this->name ?: '_untitled_';

        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        return CompiledYamlFile::instance($locator->findResource("gantry-positions://{$position}/{$name}.yaml", true, $save));
    }

    /**
     * Find unused name with number appended.
     */
    protected function findFreeName()
    {
        $position = $this->position ?: '_unassigned_';
        $name = $this->get('type');
        $name = $name == 'particle' ? $this->get('options.type') : $name;

        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        if (!file_exists($locator->findResource("gantry-positions://{$position}/{$name}.yaml", true, true))) {
            return $name;
        }

        $count = 1;

        do {
            $count++;
        } while (file_exists($locator->findResource("gantry-positions://{$position}/{$name}_{$count}.yaml", true, true)));

        return "{$name}_{$count}";
    }
}
