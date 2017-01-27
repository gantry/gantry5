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

use Gantry\Component\Collection\Collection;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Symfony\Component\Yaml\Yaml;

class Position extends Collection
{
    public $name;
    public $title;
    protected $modules = [];

    /**
     * Position constructor.
     *
     * @param string $name
     * @param array $items
     */
    public function __construct($name, array $items = null)
    {
        $this->name = $name;

        $this->load($items);
    }

    /**
     * Save position.
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
     * Clone position together with its modules. Returns new position.
     *
     * @param string $name
     * @return Position
     */
    public function duplicate($name)
    {
        $new = clone $this;
        $new->name = $name;
        $new->save();

        foreach ($this as $module) {
            $clone = clone $module;
            $clone->position = $name;
            $clone->save();
        }

        return $new;
    }

    /**
     * Raname module key
     *
     * @param string $name
     * @return static
     */
    public function rename($name)
    {
        $new = $this->duplicate($name);
        $this->delete();

        return $new;
    }

    /**
     * Delete position.
     *
     * @return $this
     */
    public function delete()
    {
        $file = $this->file(true);
        if ($file->exists()) {
            $file->delete();
        }

        $folder = $this->folder(true);
        if (is_dir($folder)) {
            Folder::delete($folder);
        }

        return $this;
    }

    /**
     * Update modules in the position.
     *
     * @param array $items
     * @return $this
     */
    public function update(array $items)
    {
        $list = [];
        foreach ($items as $item) {
            $name = ($item instanceof Module) ? $item->name : $item;

            $list[] = $name;
            if (!in_array($name, $this->items)) {
                $this->add($item);
            }
        }

        $remove = array_diff($this->items, $list);
        foreach ($remove as $item) {
            $module = $this->get($item);
            if ($module->position === $this->name) {
                $module->delete();
            }
        }

        $this->items = $list;

        return $this;
    }
    
    /**
     * @param Module|string $item
     * @param string        $name  Temporary name for the module.
     * @return $this
     */
    public function add($item, $name = null)
    {
        if ($item instanceof Module) {
            $this->modules[$name ?: $item->name] = $item;
            $item = $name ?: $item->name;
        }

        $this->items[] = $item;

        return $this;
    }

    public function remove($item)
    {
        if ($item instanceof Module) {
            $item = $item->name;
        }

        unset($this->modules[$item]);

        $this->items = array_diff($this->items, $item);

        return $this;
    }

    /**
     * @param $name
     * @return Module
     */
    public function get($name)
    {
        if (!isset($this->modules[$name])) {
            $this->modules[$name] = $this->loadModule($name);
        }

        return $this->modules[$name];
    }

    /**
     * Returns the value at specified offset.
     *
     * @param string $offset  The offset to retrieve.
     * @return Module
     */
    public function offsetGet($offset)
    {
        if (!isset($this->items[$offset])) {
            return null;
        }

        $name = $this->items[$offset];

        if (!isset($this->modules[$name])) {
            $this->modules[$name] = $this->loadModule($name);
        }

        return $this->modules[$name];
    }

    /**
     * Assigns a value to the specified offset.
     *
     * @param mixed $offset  The offset to assign the value to.
     * @param mixed $value   The value to set.
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof Position) {
            throw new \InvalidArgumentException('Value has to be instance of Position');
        }
        if (is_null($offset)) {
            $this->items[] = $value->name;
            $this->modules[$value->name] = $value;
        } else {
            $this->items[$offset] = $value->name;
            $this->modules[$value->name] = $value;
        }
    }

    /**
     * Unsets an offset.
     *
     * @param mixed $offset  The offset to unset.
     */
    public function offsetUnset($offset)
    {
        parent::offsetUnset($offset);

        if (!isset($this->items[$offset])) {
            return;
        }

        $name = $this->items[$offset];
        if (isset($this->modules[$name])) {
            unset($this->modules[$name]);
        }
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $items = [];
        foreach ($this->items as $key => $name) {
            $items[] = $this->offsetGet($key);
        }

        return new \ArrayIterator($items);
    }

    /**
     * @return array
     */
    public function toArray($includeModules = false)
    {
        $array = [
            'name' => $this->name,
            'title' => $this->title,
        ];

        if (!$includeModules) {
            $array['ordering'] = $this->items;

        } else {
            $list = [];
            foreach ($this->getIterator() as $key => $module) {
                $list[$key] = $module->toArray();
            }
            $array['modules'] = $list;
        }

        return $array;
    }

    /**
     * @param int $inline
     * @param int $indent
     * @param bool $includeModules
     * @return string
     */
    public function toYaml($inline = 3, $indent = 2, $includeModules = false)
    {
        return Yaml::dump($this->toArray($includeModules), $inline, $indent, true, false);
    }

    /**
     * @param bool $includeModules
     * @return string
     */
    public function toJson($includeModules = false)
    {
        return json_encode($this->toArray($includeModules));
    }

    /**
     * @return array
     */
    public function listModules()
    {
        $list = [];
        foreach ($this->items as $name) {
            $list[] = "{$this->name}/{$name}";
        }

        return $list;
    }

    /**
     * @param bool $save
     * @return string
     */
    public function folder($save = false)
    {
        return $this->locator()->findResource($this->path(), true, $save);
    }

    /**
     * @param $data
     */
    protected function load($data)
    {
        if ($data === null) {
            $file = $this->file();
            $data = $file->content();
            $file->free();
        }

        $this->title = isset($data['title']) ? $data['title'] : $this->name;

        if (isset($data['modules'])) {
            foreach ($data['modules'] as $array) {
                $this->add(new Module($array['id'], $this->name, $array), $array['id'] ?: rand());
            }

            return;
        }

        // Sort modules by ordering, if items are not listed in ordering, use alphabetical order.
        $ordering = isset($data['ordering']) ? array_flip($data['ordering']) : [];
        $path = $this->locator()->findResource($this->path());
        $files = $path ? Folder::all(
            $path,
            [
                'compare' => 'Filename',
                'pattern' => '|\.yaml$|',
                'folders' => false,
                'recursive' => false,
                'key' => 'Filename',
                'filters' => ['key' => '|\.yaml$|']
            ]
        ) : [];
        ksort($files);
        $this->items = array_keys($ordering + $files);
    }

    /**
     * @param  string $name
     * @return $this
     */
    protected function loadModule($name)
    {
        return new Module($name, $this->name);
    }

    /**
     * @param bool $save
     * @return CompiledYamlFile
     */
    protected function file($save = false)
    {
        return CompiledYamlFile::instance($this->locator()->findResource($this->path() . '.yaml', true, $save));
    }

    /**
     * @return UniformResourceLocator
     */
    protected function locator()
    {
        return Gantry::instance()['locator'];
    }

    /**
     * @return string
     */
    protected function path()
    {
        return "gantry-positions://{$this->name}";
    }

}
