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

use Gantry\Component\Collection\Collection;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Symfony\Component\Yaml\Exception\DumpException;
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
     * Raname module title
     *
     * @param string $title
     * @return $this
     */
    public function rename($title)
    {
        $this->title = $title;
        $this->save();

        return $this;
    }

    /**
     * Delete position.
     *
     * @return $this
     */
    public function delete()
    {
        $file = $this->file(true);
        $file->delete();

        $folder = $this->folder(true);
        if (is_dir($folder)) {
            Folder::delete($folder);
        }

        return $this;
    }

    /**
     * @param Module|string $item
     * @return $this
     */
    public function add($item)
    {
        if ($item instanceof Module) {
            $this->items[] = $item->name;
            $this->modules[$item->name] = $item;
        } else {
            $this->items[] = $item;
        }

        return $this;
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
        $this->items = isset($data['ordering']) ? array_values($data['ordering']) : [];
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
     * @param bool $save
     * @return string
     */
    public function folder($save = false)
    {
        return $this->locator()->findResource($this->path(), true, $save);
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
