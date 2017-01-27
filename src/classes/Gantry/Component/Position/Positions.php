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
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use RocketTheme\Toolbox\DI\Container;

class Positions extends Collection
{
    /**
     * @var array|Position[]
     */
    protected $items;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $path
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function load($path = 'gantry-positions://')
    {
        $this->path = $path;
        $positions = [];

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        /** @var UniformResourceIterator $iterator */
        $iterator = $locator->getIterator($path);

        /** @var UniformResourceIterator $info */
        foreach ($iterator as $info) {
            if (!$info->isFile() || $info->getExtension() != 'yaml') {
                continue;
            }

            $name = $info->getBasename('.yaml');
            $position = CompiledYamlFile::instance($info->getPathname())->content();

            // Only use filesystem position if it it is properly set up.
            if ($position) {
                $positions[$name] = new Position($name, $position);
            }
        }

        // Add empty positions from the layouts.
        foreach ($this->container['outlines']->positions() as $name => $title) {
            if (!isset($positions[$name])) {
                $positions[$name] = new Position($name, ['title' => $title]);
            }
        }

        ksort($positions);

        $this->items = $positions;

        return $this;
    }

    /**
     * Updates all positions with their modules from an array and saves them.
     *
     * @param array $data
     * @return $this
     */
    public function import(array $data)
    {
        foreach ($data as $pos) {
            $list = [];
            $position = $pos['name'];
            foreach ($pos['modules'] as $item) {
                $name = !empty($item['id']) ? $item['id'] : '';

                if ($name && !empty($item['position'])) {
                    $module = $this[$item['position']]->get($name);

                    if ($position !== $item['position']) {
                        $module->delete();
                    }
                } else {
                    $module = new Module($name, $position);
                }
                $module->update($item)->save($name, $position);

                $list[] = $module;
            }

            $this[$pos['name']]->update($list)->save();
        }

        return $this;
    }

    /**
     * @param Position $item
     * @return $this
     */
    public function add($item)
    {
        if ($item instanceof Position) {
            $this->items[$item->name] = $item;
        }

        return $this;
    }

    /**
     * @param string $title
     * @param string $id
     *
     * @return string
     * @throws \RuntimeException
     */
    public function create($title = 'Untitled', $id = null)
    {
        $name = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $id ?: $title));

        if (!$name) {
            throw new \RuntimeException("Position needs a name", 400);
        }

        $name = $this->findFreeName($name);

        $position = new Position($name, ['title' => $title]);
        $position->save();

        return $name;
    }

    /**
     * @param string $id
     * @param string $new
     *
     * @return string
     * @throws \RuntimeException
     */
    public function duplicate($id, $new = null)
    {
        if (!isset($this->items[$id])) {
            throw new \RuntimeException(sprintf("Duplicating Position failed: '%s' not found.", $id), 400);
        }

        $new = $this->findFreeName($new ? strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $new)) : $id);

        $position = $this->items[$id];
        $new = $position->duplicate($new);

        return $new->name;
    }

    /**
     * @param string $id
     * @param string $new
     *
     * @return string
     * @throws \RuntimeException
     */
    public function rename($id, $new)
    {
        if (!isset($this->items[$id])) {
            throw new \RuntimeException(sprintf("Renaming Position failed: '%s' not found.", $id), 400);
        }

        $newId = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $new));

        if (isset($this->items[$newId])) {
            throw new \RuntimeException(sprintf("Renaming Position failed: '%s' already exists.", $newId), 400);
        }

        $position = $this->items[$id];
        $position->rename($new);

        return $position->name;
    }

    /**
     * @param string $id
     *
     * @throws \RuntimeException
     */
    public function delete($id)
    {
        if (!isset($this->items[$id])) {
            throw new \RuntimeException(sprintf("Deleting Position failed: '%s' not found.", $id), 400);
        }

        $position = $this->items[$id];
        $position->delete();
    }

    /**
     * Find unused name with number appended to it when duplicating an position.
     *
     * @param string $id
     *
     * @return string
     */
    protected function findFreeName($id)
    {
        if (!isset($this->items[$id])) {
            return $id;
        }

        $name  = $id;
        $count = 0;
        if (preg_match('|^(?:_)?(.*?)(?:_(\d+))?$|ui', $id, $matches)) {
            $matches += ['', '', ''];
            list (, $name, $count) = $matches;
        }

        $count = max(1, $count);

        do {
            $count++;
        } while (isset($this->items["{$name}_{$count}"]));

        return "{$name}_{$count}";
    }
}
