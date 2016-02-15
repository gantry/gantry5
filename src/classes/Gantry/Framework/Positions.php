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

namespace Gantry\Framework;

use FilesystemIterator;
use Gantry\Component\Collection\Collection;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use RocketTheme\Toolbox\DI\Container;

class Positions extends Collection
{
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

    public function all()
    {
        return $this;
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

        $iterator = $this->getFilesystemIterator($path);

        $positions = $this->container['configurations']->positions();

        /** @var FilesystemIterator $info */
        foreach ($iterator as $name => $info) {
            if (isset($positions[$name]) || !$info->isDir() || $info->isDot()) {
                continue;
            }
            $positions[$name] = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $name)));
        }

        asort($positions);

        $this->items = $positions;

        return $this;
    }

    /**
     * @param string $title
     *
     * @return string
     * @throws \RuntimeException
     */
    public function create($title = 'Untitled')
    {
        $name = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $title));

        if (!$name) {
            throw new \RuntimeException("Position needs a name", 400);
        }

        $name = $this->findFreeName($name);

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $path = $locator->findResource("{$this->path}/{$name}", true, true);

        Folder::create($path);

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
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $new = $this->findFreeName($new ? strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $new)) : $id);

        $newPath = $locator->findResource("{$this->path}/{$new}", true, true);

        $path = $locator->findResource("{$this->path}/{$id}");
        try {
            if (!$path || !is_dir($path)) {
                Folder::create($newPath);
            } else {
                Folder::copy($path, $newPath);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Duplicating Position failed: ', $e->getMessage()), 500, $e);
        }

        return basename($newPath);
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
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $path = $locator->findResource("{$this->path}/{$id}", true, true);
        if (!$path || !is_dir($path)) {
            throw new \RuntimeException('Position not found', 404);
        }

        $new = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $new));

        $newPath = $locator->findResource("{$this->path}/{$new}", true, true);
        if (is_file($newPath)) {
            throw new \RuntimeException("Position '{$new}' already exists.", 400);
        }

        try {
            Folder::move($path, $newPath);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Renaming Position failed: ', $e->getMessage()), 500, $e);
        }

        return $new;
    }

    /**
     * @param string $id
     *
     * @throws \RuntimeException
     */
    public function delete($id)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $path = $locator->findResource("{$this->path}/{$id}", true, true);

        if (!is_dir($path)) {
            return;
        }

        Folder::delete($path);
    }

    protected function getFilesystemIterator($path)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $custom = $locator->findResource($path, true, true);
        if (is_dir($custom)) {
            /** @var FilesystemIterator $iterator */
            $iterator = new FilesystemIterator(
                $custom,
                FilesystemIterator::CURRENT_AS_SELF | FilesystemIterator::KEY_AS_FILENAME |
                FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS
            );
        } else {
            /** @var UniformResourceIterator $iterator */
            $iterator = $locator->getIterator(
                $path,
                UniformResourceIterator::CURRENT_AS_SELF | UniformResourceIterator::KEY_AS_FILENAME |
                UniformResourceIterator::UNIX_PATHS | UniformResourceIterator::SKIP_DOTS
            );
        }

        return $iterator;
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
