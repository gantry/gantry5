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

namespace Gantry\Component\Layout;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Outlines;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccess;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\ExportInterface;
use RocketTheme\Toolbox\ArrayTraits\Iterator;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Layout
 */
class Layout implements \ArrayAccess, \Iterator, ExportInterface
{
    use ArrayAccess, Iterator, Export;

    protected static $instances = [];
    protected static $indexes = [];

    public $name;
    public $timestamp = 0;
    public $preset = [];
    protected $exists;
    protected $items;
    protected $references;
    protected $types;

    /**
     * @return array
     */
    public static function presets()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        /** @var UniformResourceIterator $iterator */
        $iterator = $locator->getIterator(
            'gantry-layouts://',
            UniformResourceIterator::CURRENT_AS_SELF | UniformResourceIterator::UNIX_PATHS | UniformResourceIterator::SKIP_DOTS
        );

        $files = [];
        /** @var UniformResourceIterator $info */
        foreach ($iterator as $info) {
            $name = $info->getBasename('.yaml');
            if (!$info->isFile() || $info->getExtension() != 'yaml' || $name[0] == '.') {
                continue;
            }
            $files[] = $name;
        }

        sort($files);

        $results = ['user' => [], 'system' => []];
        foreach ($files as $preset) {
            $scope = $preset && $preset[0] !== '_' ? 'user' : 'system';
            $results[$scope][$preset] = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $preset)));
        }

        return $results;
    }

    /**
     * @param string $name
     * @return array
     * @throws \RuntimeException
     */
    public static function preset($name)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $filename = $locator->findResource("gantry-layouts://{$name}.yaml");

        if (!$filename) {
            throw new \RuntimeException("Preset '{$name}' not found", 404);
        }

        $layout = LayoutReader::read($filename);
        $layout['preset']['name'] = $name;
        $layout['preset']['timestamp'] = filemtime($filename);

        return $layout;
    }

    /**
     * @param  string $name
     * @return Layout
     */
    public static function instance($name)
    {
        if (!isset(static::$instances[$name])) {
            static::$instances[$name] = static::load($name);
        }

        return static::$instances[$name];
    }

    /**
     * @param  string $name
     * @return Layout
     */
    public static function index($name)
    {
        if (!isset(static::$indexes[$name])) {
            static::$indexes[$name] = static::loadIndex($name, true);
        }

        return static::$indexes[$name];
    }

    /**
     * @param string $name
     * @param array $items
     */
    public function __construct($name, array $items = null, array $preset = null)
    {
        $this->name = $name;
        $this->items = (array) $items;
        $this->exists = $items !== null;

        // Add preset data from the layout.
        if ($preset) {
            $this->preset = $preset;
        } elseif (isset($this->items['preset'])) {
            $this->preset = (array) $this->items['preset'];
        }

        unset($this->items['preset']);

        $this->preset += [
            'name' => '',
            'timestamp' => 0,
            'image' => 'gantry-admin://images/layouts/default.png'
        ];
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * Build separate meta-information from the layout.
     *
     * @return array
     */
    public function buildIndex()
    {
        $positions = $this->positions();

        return [
            'name' => $this->name,
            'timestamp' => $this->timestamp,
            'preset' => $this->preset,
            'positions' => $positions
        ];
    }

    /**
     * Save layout.
     *
     * @return $this
     */
    public function save()
    {
        if (!$this->name) {
            throw new \LogicException('Cannot save unnamed layout');
        }

        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $name = strtolower(preg_replace('|[^a-z\d_-]|ui', '_', $this->name));

        $filename = $locator->findResource("gantry-config://{$name}/layout.yaml", true, true);
        $file = CompiledYamlFile::instance($filename);
        $file->settings(['inline' => 20]);
        $file->save(['preset' => $this->preset, 'children' => json_decode(json_encode($this->items), true)]);
        $file->free();

        $this->exists = true;

        return $this;
    }

    /**
     * Save index.
     *
     * @return $this
     */
    public function saveIndex()
    {
        if (!$this->name) {
            throw new \LogicException('Cannot save unnamed layout');
        }

        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $filename = $locator->findResource("gantry-config://{$this->name}/index.yaml", true, true);
        $cache = $locator->findResource("gantry-cache://{$this->name}/compiled/yaml", true, true);
        $file = CompiledYamlFile::instance($filename);

        // Attempt to lock the file for writing.
        try {
            $file->lock(false);
        } catch (\Exception $e) {
            // Another process has locked the file; we will check this in a bit.
        }

        $index = $this->buildIndex();

        // If file wasn't already locked by another process, save it.
        if ($file->locked() !== false) {
            $file->setCachePath($cache)->settings(['inline' => 20]);
            $file->save($index);
            $file->unlock();
        }
        $file->free();

        static::$indexes['name'] = $index;

        return $this;
    }

    /**
     * @return array
     */
    public function references()
    {
        if (!isset($this->references)) {
            $this->initReferences();
        }

        return $this->references;
    }

    /**
     * @param string $type
     * @param string $subtype
     * @return array
     */
    public function referencesByType($type = null, $subtype = null)
    {
        if (!isset($this->references)) {
            $this->initReferences();
        }

        if (!$type) {
            return $this->types;
        } elseif (!$subtype) {
            return isset($this->types[$type]) ? $this->types[$type] : [];
        }
        return isset($this->types[$type][$subtype]) ? $this->types[$type][$subtype] : [];
    }

    /**
     * Return list of positions (key) with their titles (value).
     *
     * @return array Array of position => title
     */
    public function positions()
    {
        $positions = $this->referencesByType('position', 'position');

        $list = [];
        foreach($positions as $position) {
            if (!isset($position->attributes->key)) {
                continue;
            }
            $list[$position->attributes->key] = $position->title;
        }

        return $list;
    }

    /**
     * @param string $id
     * @return \stdClass
     */
    public function find($id)
    {
        if (!isset($this->references)) {
            $this->initReferences();
        }

        if (!isset($this->references[$id])) {
            return new \stdClass;
        }

        return $this->references[$id];
    }


    public function clearSections()
    {
        $this->items = $this->clearChildren($this->items);

        return $this;
    }

    protected function clearChildren(&$items)
    {
        foreach ($items as $key => &$item) {
            if (!empty($item->children)) {
                $this->children = $this->clearChildren($item->children);
            }

            if (empty($item->children) && in_array($item->type, ['grid', 'block', 'particle', 'position', 'spacer', 'pagecontent'])) {
                unset($items[$key]);
            }
        }

        return array_values($items);
    }

    public function copySections(array $old)
    {
        if (!isset($this->references)) {
            $this->initReferences();
        }

        /** @var Layout $old */
        $old = new static($this->name, $old);

        $leftover = [];

        // Copy normal sections.
        $data = $old->referencesByType('section', 'section');
        if (isset($this->types['section']['section'])) {
            $sections = &$this->types['section']['section'];

            $this->copyData($data, $sections, $leftover);
        }

        // Copy offcanvas.
        $data = $old->referencesByType('offcanvas', 'offcanvas');
        if (isset($this->types['offcanvas']['offcanvas'])) {
            $offcanvas = &$this->types['offcanvas']['offcanvas'];

            $this->copyData($data, $offcanvas, $leftover);
        }

        // Copy atoms.
        $data = $old->referencesByType('atoms', 'atoms');
        if (isset($this->types['atoms']['atoms'])) {
            $atoms = &$this->types['atoms']['atoms'];

            $this->copyData($data, $atoms, $leftover);
        }

        return $leftover;
    }

    protected function copyData(array $data, array &$sections, array &$leftover)
    {
        foreach ($data as $item) {
            $found = false;
            foreach ($sections as &$section) {
                if ($section->title === $item->title) {
                    $found = true;
                    $section = $item;
                    break;
                }
            }
            if (!$found && !empty($item->children)) {
                $leftover[] = $item->title;
            }
        }
    }

    /**
     * @param array $items
     */
    protected function initReferences(array &$items = null)
    {
        if ($items === null) {
            $items = &$this->items;
            $this->references = [];
            $this->types = [];
        }

        foreach ($items as $key => &$item) {
            if (is_object($item)) {
                if (isset($item->id)) {
                    $this->references[$item->id] = &$item;
                }
                $type = $item->type;
                $subtype = !empty($item->subtype) ? $item->subtype : $type;

                $this->types[$type][$subtype][] = &$item;

                if (isset($item->children) && is_array($item->children)) {
                    $this->initReferences($item->children);
                }
            }
        }
    }

    /**
     * @param  string $name
     * @return static
     */
    public static function load($name)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $layout = null;
        $preset = null;
        $filename = $locator("gantry-config://{$name}/layout.yaml");

        // If layout file doesn't exists, figure out what preset was used.
        if (!$filename) {
            $index = static::loadIndex($name);
            $preset = $index['preset']['name'];

            try {
                $layout = static::preset($preset);
            } catch (\Exception $e) {
                // Layout doesn't exist, do nothing.
            }
        } else {
            $layout = LayoutReader::read($filename);
        }

        return new static($name, $layout);
    }

    /**
     * @param  string $name
     * @return static
     */
    public static function loadIndex($name, $autoSave = false)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        // Attempt to load the index file.
        $indexFile = $locator("gantry-config://{$name}/index.yaml");
        if ($indexFile) {
            $file = CompiledYamlFile::instance($indexFile);
            $index = $file->content();
            $file->free();
        }

        // Find out the currently used layout file.
        $layoutFile = $locator("gantry-config://{$name}/layout.yaml");
        if (!$layoutFile) {
            /** @var Outlines $configurations */
            $configurations = $gantry['configurations'];

            $preset = isset($index['preset']['name']) ? $index['preset']['name'] : $configurations->preset($name);

            $layoutFile = $locator("gantry-layouts://{$preset}.yaml");
        }

        // Get timestamp for the layout file.
        $timestamp = $layoutFile ? filemtime($layoutFile) : 0;

        // If layout index file doesn't exist or is not up to date, build it.
        if (!isset($index['timestamp']) || $index['timestamp'] != $timestamp) {
            $layout = isset($preset) ? new static($name, static::preset($preset)) : static::instance($name);
            $layout->timestamp = $timestamp;
            $index = $layout->buildIndex();
        }

        if ($autoSave && isset($layout)) {
            $layout->saveIndex();
        }

        $index += [
            'name' => $name,
            'timestamp' => $timestamp,
            'preset' => [
                'name' => '',
                'image' => 'gantry-admin://images/layouts/default.png'
            ],
            'positions' => []
        ];

        return $index;
    }
}
