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

namespace Gantry\Component\Outline;

use FilesystemIterator;
use Gantry\Component\Collection\Collection;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout;
use Gantry\Framework\Atoms;
use RocketTheme\Toolbox\DI\Container;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class OutlineCollection extends Collection
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $path;

    /**
     * @param Container $container
     * @param array $items
     */
    public function __construct(Container $container, $items = [])
    {
        $this->container = $container;
        $this->items = $items;
    }

    /**
     * @param string $id
     * @return string|null
     */
    public function name($id)
    {
        return isset($this->items[$id]) ? $this->items[$id] : null;
    }

    /**
     * @param string $id
     * @return string
     */
    public function title($id)
    {
        return isset($this->items[$id]) ? $this->items[$id] : $id;
    }


    public function all()
    {
        return $this;
    }

    public function system()
    {
        foreach ($this->items as $key => $item) {
            if (substr($key, 0, 1) !== '_') {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function user()
    {
        foreach ($this->items as $key => $item) {
            if (substr($key, 0, 1) === '_' || $key == 'default') {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function filter(array $include = null)
    {
        if ($include !== null) {
            foreach ($this->items as $key => $item) {
                if (!in_array($key, $include)) {
                    unset($this->items[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Returns list of all positions defined in all outlines.
     *
     * @return array
     */
    public function positions()
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            try {
                $index = Layout::index($name);

                $list += $index['positions'];
            } catch (\Exception $e) {
                // Layout cannot be read. We will just skip it instead of throwing an exception.
            }
        }

        return $list;
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
            try {
                $index = Layout::index($name);
            } catch (\Exception $e) {
                // Layout cannot be read. We will just skip it instead of throwing an exception.
                continue;
            }

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
            try {
                $index = Layout::index($name);
            } catch (\Exception $e) {
                // Layout cannot be read. We will just skip it instead of throwing an exception.
                continue;
            }

            if (isset($index['particles'][$particle])) {
                $ids = $index['particles'][$particle];
                if (!$includeInherited && !empty($index['inherit'])) {
                    foreach ($index['inherit'] as $items) {
                        foreach ((array) $items as $id => $inheritId) {
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
     * @param string $type
     * @param bool $includeInherited
     * @return array
     */
    public function getOutlinesWithAtom($type, $includeInherited = true)
    {
        $list = [];

        foreach ($this->items as $name => $title) {
            $file = CompiledYamlFile::instance("gantry-theme://config/{$name}/page/head.yaml");
            $index = $file->content();
            $file->free();
            if (isset($index['atoms'])) {
                foreach ($index['atoms'] as $atom) {
                    if (!empty($atom['id']) && $atom['type'] === $type && ($includeInherited || empty($atom['inherit']))) {
                        $list[$name] = $title;
                    }
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
                    foreach ((array) $items as $id => $inheritId) {
                        unset($list[$id]);
                    }
                }
            }
        }

        $layout = Layout::instance($outline);

        foreach ($list as $id => $title) {
            $item = clone $layout->find($id);
            $block = $layout->block($id);
            $item->block = isset($block->attributes) ? $block->attributes : new \stdClass();
            $list[$id] = $item;
        }

        return $list;
    }


    /**
     * @param string $outline
     * @param string $type
     * @param bool $includeInherited
     * @return array
     */
    public function getAtomInstances($outline, $type, $includeInherited = true)
    {
        $list = [];

        $file = CompiledYamlFile::instance("gantry-theme://config/{$outline}/page/head.yaml");
        $head = $file->content();
        $file->free();
        if (isset($head['atoms'])) {
            foreach ($head['atoms'] as $atom) {
                if (!empty($atom['id']) && $atom['type'] === $type && ($includeInherited || empty($atom['inherit']['outline']))) {
                    $list[$atom['id']] = (object) $atom;
                }
            }
        }

        return $list;
    }

    /**
     * Return list of outlines which are inheriting the specified atom.
     *
     * @param string $outline
     * @param string $id
     * @return array
     */
    public function getInheritingOutlinesWithAtom($outline, $id = null)
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            $file = CompiledYamlFile::instance("gantry-theme://config/{$name}/page/head.yaml");
            $head = $file->content();
            $file->free();

            if (isset($head['atoms'])) {
                foreach ($head['atoms'] as $atom) {
                    if (!empty($atom['inherit']['outline']) && $atom['inherit']['outline'] == $outline && (!$id || $atom['inherit']['atom'] == $id)) {
                        $list[$name] = $title;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Return list of outlines which are inheriting the specified outline.
     *
     * You can additionally pass section or particle id to filter the results for only that type.
     *
     * @param string $outline
     * @param string|array $id
     * @return array
     */
    public function getInheritingOutlines($outline, $id = null)
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            try {
                $index = Layout::index($name);
            } catch (\Exception $e) {
                // Layout cannot be read. We will just skip it instead of throwing an exception.
                continue;
            }

            if (!empty($index['inherit'][$outline]) && (!$id || array_intersect((array) $id, $index['inherit'][$outline]))) {
                $list[$name] = $title;
            }
        }

        return $list;
    }

    /**
     * Return list of outlines inherited by the specified outline.
     *
     * You can additionally pass section or particle id to filter the results for only that type.
     *
     * @param string $outline
     * @param string $id
     * @return array
     */
    public function getInheritedOutlines($outline, $id = null)
    {
        try {
            $index = Layout::index($outline);
        } catch (\Exception $e) {
            // Layout cannot be read. We will just return nothing instead of throwing an exception.
            return [];
        }

        $list = [];
        foreach ($index['inherit'] as $name => $inherited) {
            if (!$id || array_intersect_key((array) $id, $inherited[$id])) {
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
     * @return array
     */
    public function layoutPreset($id)
    {
        $layout = Layout::load($id);
        $preset = $layout->preset;

        unset($layout);

        return $preset;
    }

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
     * @param string|null $id
     * @param string $title
     * @param string|array $preset
     * @return string
     * @throws \RuntimeException
     */
    public function create($id, $title = null, $preset = null)
    {
        $title = $title ?: 'Untitled';
        $name = ltrim(strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $id ?: $title)), '_');

        if (!$name) {
            throw new \RuntimeException("Outline needs a name", 400);
        }

        if ($name === 'default') {
            throw new \RuntimeException("Outline cannot use reserved name '{$name}'", 400);
        }

        $name = $this->findFreeName($name);
        if (!$id) {
            $title = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $name)));
        }

        if (!is_array($preset)) {
            // Load preset.
            $preset = Layout::preset($preset ?: 'default');
        }

        // Create layout and index for the new layout.
        $layout = new Layout($name, $preset);
        $layout->save()->saveIndex();

        $this->items[$name] = $title;

        return $name;
    }

    /**
     * @param string $id
     * @param string $title
     * @param bool $inherit
     * @return string
     * @throws \RuntimeException
     */
    public function duplicate($id, $title = null, $inherit = false)
    {
        if (!$this->canDuplicate($id)) {
            throw new \RuntimeException("Outline '$id' cannot be duplicated", 400);
        }

        $layout = Layout::load($id);
        if ($inherit) {
            $layout->inheritAll()->clean();
        }

        $new = $this->create(null, $title, $layout->toArray() + ['preset' => $layout->preset]);

        if ($id === 'default') {
            // For Base Outline we're done.
            return $new;
        }

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $path = $locator->findResource("{$this->path}/{$id}");
        if (!$path) {
            // Nothing to copy.
            return $new;
        }

        $newPath = $locator->findResource("{$this->path}/{$new}", true, true);

        try {
            // Copy everything over except index, layout and assignments.
            Folder::copy($path, $newPath, '/^(index|layout|assignments)\..*$/');
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Duplicating Outline failed: ', $e->getMessage()), 500, $e);
        }

        return $new;
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
                $this->layout($outline)->updateInheritance($id, $folder)->save()->saveIndex();
            }
            foreach ($this->getInheritingOutlinesWithAtom($id) as $outline => $title) {
                Atoms::instance($outline)->updateInheritance($id, $folder)->save();
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
            $this->layout($outline)->updateInheritance($id)->save()->saveIndex();
        }
        foreach ($this->getInheritingOutlinesWithAtom($id) as $outline => $title) {
            Atoms::instance($outline)->updateInheritance($id)->save();
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
        if (!isset($this->items[$id])) {
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
     * @param array $outlines
     * @return array
     */
    protected function addDefaults(array $outlines)
    {
        return [
            'default' => 'Base Outline',
            '_body_only' => 'Body Only',
            '_error' => 'Error',
            '_offline' => 'Offline'
        ] + $outlines;
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
