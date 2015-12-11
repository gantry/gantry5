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

    public function __construct(Container $container, $items = [])
    {
        $this->container = $container;
        $this->items     = $items;
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
    public function load($path = 'gantry-config://positions')
    {
        $this->path = $path;

        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $iterator = $this->getFilesystemIterator($path);

        $files = [];
        /** @var FilesystemIterator $info */
        foreach ($iterator as $name => $info) {
            if ($info->isDir() || $info->isDot() || !is_file($info->getPathname())) {
                continue;
            }
            $files[$name] = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $name)));
        }
        asort($files);

        $this->items = $files;

        return $this;
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
     * @param string $title
     *
     * @return string
     * @throws \RuntimeException
     */
    public function create($title = 'Untitled')
    {
        $name = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $title));

        if (!$name) {
            throw new \RuntimeException("Positions needs a name", 400);
        }

        $name = $this->findFreeName($name);

        // Create index file for the new layout.
        /*$layout = new Layout($name, $preset);
        $layout->saveIndex();*/

        return $name;
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
        if (!isset($this->items[$id . '.yaml'])) {
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

    /**
     * @param string $id
     *
     * @return string
     * @throws \RuntimeException
     */
    public function duplicate($id)
    {
        $gantry = $this->container;
        $title = $id;
        $id = $id . '.yaml';

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $path = $locator->findResource("{$this->path}/{$id}");
        if (!$path || is_dir($path)) {
            throw new \RuntimeException('Position not found', 404);
        }

        $file = $this->findFreeName(strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $title)));

        $newPath = $locator->findResource("{$this->path}/{$file}.yaml", true, true);

        try {
            @copy($path, $newPath);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Duplicating Position failed: ', $e->getMessage()), 500, $e);
        }

        return basename($file);
    }

    /**
     * @param string $id
     * @param string $title
     *
     * @return string
     * @throws \RuntimeException
     */
    public function rename($id, $title)
    {
        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $path = $locator->findResource("{$this->path}/{$id}", true, true);
        if (!$path || is_dir($path)) {
            throw new \RuntimeException('Position not found', 404);
        }

        $file = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $title));

        $newPath = $locator->findResource("{$this->path}/{$file}", true, true);
        if (is_file($newPath)) {
            throw new \RuntimeException("Position '$id' already exists.", 400);
        }

        try {
            @rename($path, $newPath . '.yaml');
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Renaming Position failed: ', $e->getMessage()), 500, $e);
        }

        return $file;
    }

    /**
     * @param string $id
     *
     * @throws \RuntimeException
     */
    public function delete($id)
    {
        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $path    = $locator->findResource("{$this->path}/{$id}", true, true);
        if (is_dir($path)) {
            throw new \RuntimeException('Position not found', 404);
        }

        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
