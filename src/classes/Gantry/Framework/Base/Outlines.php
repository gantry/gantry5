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

namespace Gantry\Framework\Base;

use FilesystemIterator;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Outline\AbstractOutlineCollection;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Outlines extends AbstractOutlineCollection
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @param string $path
     * @return $this
     * @throws \RuntimeException
     */
    public function load($path = 'gantry-config://')
    {
        $this->path = $path;

        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $iterator = $this->getFilesystemIterator($path);

        $files = [];
        /** @var FilesystemIterator $info */
        foreach ($iterator as $name => $info) {
            if (!$info->isDir() || $name[0] == '.' || !is_file($info->getPathname() . '/index.yaml')) {
                continue;
            }
            $files[$name] = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $name)));
        }

        // In case if someone removed default from custom configuration, make sure it doesn't count.
        if (!isset($files['default']) && !$locator->findResource("{$path}/default")) {
            throw new \RuntimeException('Fatal error: Theme does not have Base Outline');
        }

        unset($files['default']);
        unset($files['menu']);

        asort($files);

        $this->items = $this->addDefaults($files);

        return $this;
    }

    /**
     * Returns list of all positions defined in outsets.
     *
     * @return array
     */
    public function positions()
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            $index = Layout::index($name);

            $list += $index['positions'];
        }

        return $list;
    }

    /**
     * @param int|string $id
     * @return int|string
     */
    public function preset($id)
    {
        return $id;
    }

    /**
     * @param int|string $id
     * @return Layout
     */
    public function layout($id)
    {
        return Layout::load($id);
    }

    /**
     * @param int|string $id
     * @return Layout
     */
    public function layoutPreset($id)
    {
        $layout = Layout::load($id);
        $preset = $layout->preset;

        unset($layout);

        return $preset;
    }

    /**
     * @param string $title
     * @param string $preset
     * @return string
     * @throws \RuntimeException
     */
    public function create($title = 'Untitled', $preset = 'default')
    {
        $name = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $title));

        if (!$name) {
            throw new \RuntimeException("Outline needs a name", 400);
        }

        if ($name === 'default' || $name[0] === '_') {
            throw new \RuntimeException("Outline cannot use reserved name '{$name}'", 400);
        }

        $name = $this->findFreeName($name);

        // Load preset.
        $preset = Layout::preset($preset);

        // Create index file for the new layout.
        $layout = new Layout($name, $preset);
        $layout->saveIndex();

        return $name;
    }

    /**
     * @param string $id
     * @return string
     * @throws \RuntimeException
     */
    public function duplicate($id)
    {
        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $path = $locator->findResource("{$this->path}/{$id}");
        if (!$path || !is_dir($path)) {
            throw new \RuntimeException('Outline not found', 404);
        }

        $folder = $this->findFreeName(strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $id)));

        $newPath = $locator->findResource("{$this->path}/{$folder}", true, true);

        try {
            Folder::copy($path, $newPath, '/assignments/');
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Duplicating Outline failed: ', $e->getMessage()), 500, $e);
        }

        return basename($folder);
    }

    /**
     * @param string $id
     * @param string $title
     * @return string
     * @throws \RuntimeException
     */
    public function rename($id, $title)
    {
        if (!$this->canDelete($id)) {
            throw new \RuntimeException("Outline '$id' cannot be renamed", 400);
        }

        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $path = $locator->findResource("{$this->path}/{$id}", true, true);
        if (!$path || !is_dir($path)) {
            throw new \RuntimeException('Outline not found', 404);
        }

        $folder = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $title));

        if ($folder === 'default' || $folder[0] === '_') {
            throw new \RuntimeException("Outline cannot use reserved name '{$folder}'", 400);
        }

        $newPath = $locator->findResource("{$this->path}/{$folder}", true, true);
        if (is_dir($newPath)) {
            throw new \RuntimeException("Outline '$id' already exists.", 400);
        }

        try {
            Folder::move($path, $newPath);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Renaming Outline failed: ', $e->getMessage()), 500, $e);
        }

        return $folder;
    }

    /**
     * @param string $id
     * @throws \RuntimeException
     */
    public function delete($id)
    {
        if (!$this->canDelete($id)) {
            throw new \RuntimeException("Outline '$id' cannot be deleted", 400);
        }

        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $path = $locator->findResource("{$this->path}/{$id}", true, true);
        if (!is_dir($path)) {
            throw new \RuntimeException('Outline not found', 404);
        }

        if (file_exists($path)) {
            Folder::delete($path);
        }
    }

    /**
     * @param string $id
     * @return boolean
     */
    public function canDelete($id)
    {
        if (!$id || $id[0] === '_' || $id === 'default') {
            return false;
        }

        return true;
    }

    /**
     * @param string $id
     * @return boolean
     */
    public function isDefault($id)
    {
        return $id === 'default';
    }

    /**
     * @param array $configurations
     * @return array
     */
    protected function addDefaults(array $configurations)
    {
        return [
            'default' => 'Base Outline',
            '_body_only' => 'Body Only',
            '_error' => 'Error',
            '_offline' => 'Offline'
        ] + $configurations;
    }

    /**
     * Find unused name with number appended to it when duplicating an outline.
     *
     * @param string $id
     * @return string
     */
    protected function findFreeName($id)
    {
        if (!isset($this->items[$id])) {
            return $id;
        }

        $name = $id;
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
}
