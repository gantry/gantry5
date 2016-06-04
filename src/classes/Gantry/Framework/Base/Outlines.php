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

        $iterator = $this->getFilesystemIterator($path);

        $files = [];
        /** @var FilesystemIterator $info */
        foreach ($iterator as $name => $info) {
            if (!$info->isDir() || $name[0] == '.' || !is_file($info->getPathname() . '/index.yaml')) {
                continue;
            }
            $files[$name] = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $name)));
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
     * @param string $id
     * @return string
     */
    public function title($id)
    {
        return isset($this->items[$id]) ? $this->items[$id] : $id;
    }

    /**
     * @param string $section
     * @param bool $includeInherited
     * @return array
     */
    public function getOutlinesWithSection($section, $includeInherited = true)
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            $index = Layout::index($name);
            if (isset($index['sections'][$section])) {
                if (!$includeInherited) {
                    foreach ($index['inherit'] as $outline => $items) {
                        if (is_array($items) && in_array($section, $items)) {
                            continue 2;
                        }
                    }
                }
                $list[$name] = $title;
            }
        }

        return $list;
    }

    /**
     * @param string $particle
     * @param bool $includeInherited
     * @return array
     */
    public function getOutlinesWithParticle($particle, $includeInherited = true)
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            $index = Layout::index($name);
            if (isset($index['particles'][$particle])) {
                $ids = $index['particles'][$particle];
                if (!$includeInherited && !empty($index['inherit'])) {
                    foreach ($index['inherit'] as $items) {
                        foreach ((array) $items as $id) {
                            unset($ids[$id]);
                        }
                    }
                }
                if ($ids) {
                    $list[$name] = $title;
                }
            }
        }

        return $list;
    }

    /**
     * @param string $particle
     * @param bool $includeInherited
     * @return array
     */
    public function getAllParticleInstances($particle, $includeInherited = true)
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            $list += $this->getParticleInstances($name, $particle, $includeInherited);
        }

        return $list;
    }

    /**
     * @param string $outline
     * @param string $particle
     * @param bool $includeInherited
     * @return array
     */
    public function getParticleInstances($outline, $particle, $includeInherited = true)
    {
        $list = [];
        $index = Layout::index($outline);
        if (isset($index['particles'][$particle])) {
            $list = $index['particles'][$particle];
            if (!$includeInherited && !empty($index['inherit'])) {
                foreach ($index['inherit'] as $items) {
                    foreach ((array) $items as $id) {
                        unset($list[$id]);
                    }
                }
            }
        }

        $layout = Layout::instance($outline);

        foreach ($list as $id => $title) {
            $item = clone $layout->find($id);
            $block = $layout->block($id);
            $item->block = $block ? $block->attributes : new \stdClass();
            $list[$id] = $item;
        }

        return $list;
    }

    /**
     * Return list of outlines which are inheriting the specified outline.
     *
     * You can additionally pass particle id to filter the results for only that particle.
     *
     * @param string $outline
     * @param string $particle
     * @return array
     */
    public function getInheritingOutlines($outline, $particle = null)
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            $index = Layout::index($name);

            if (!empty($index['inherit'][$outline]) && (!$particle || isset($index['inherit'][$outline][$particle]))) {
                $list[$name] = $title;
            }
        }

        return $list;

    }

    /**
     * Return list of outlines inherited by the specified outline.
     *
     * You can additionally pass particle id to filter the results for only that particle.
     *
     * @param string $outline
     * @param string $particle
     * @return array
     */
    public function getInheritedOutlines($outline, $particle = null)
    {
        $index = Layout::index($outline);

        $list = [];
        foreach ($index['inherit'] as $name => $inherited) {
            if (!$particle || in_array($particle, $inherited)) {
                $list[$name] = isset($this->items[$name]) ? $this->items[$name] : $name;
            }
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

        $this->items[$name] = $title;

        return $name;
    }

    /**
     * @param string $id
     * @param string $title
     * @return string
     * @throws \RuntimeException
     */
    public function duplicate($id, $title = null)
    {
        if (!$this->canDuplicate($id)) {
            throw new \RuntimeException("Outline '$id' cannot be duplicated", 400);
        }

        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $path = $locator->findResource("{$this->path}/{$id}");
        if (!$path || !is_dir($path)) {
            throw new \RuntimeException('Outline not found', 404);
        }

        $title = $title ?: (!$title && $id === 'default' ? 'untitled' : $id);
        $folder = $this->findFreeName(strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $title)));

        $newPath = $locator->findResource("{$this->path}/{$folder}", true, true);

        try {
            Folder::copy($path, $newPath, $id === 'default' ? '/^(?!(index|layout)).*$/' : '/assignments/');
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Duplicating Outline failed: ', $e->getMessage()), 500, $e);
        }

        $this->items[$folder] = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], basename($newPath))));

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
            foreach ($this->getInheritingOutlines($id) as $outline => $title) {
                $layout = $this->layout($outline);
                $layout->updateInheritance($id, $folder)->save()->saveIndex();
            }

            Folder::move($path, $newPath);

        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Renaming Outline failed: %s', $e->getMessage()), 500, $e);
        }

        $this->items[$id] = $title;

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

        foreach ($this->getInheritingOutlines($id) as $outline => $title) {
            $layout = $this->layout($outline);
            $layout->updateInheritance($id)->save()->saveIndex();
        }

        if (file_exists($path)) {
            Folder::delete($path);
        }

        unset($this->items[$id]);
    }

    /**
     * @param string $id
     * @return boolean
     */
    public function canDuplicate($id)
    {
        if (!$id) {
            return false;
        }

        return true;
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
