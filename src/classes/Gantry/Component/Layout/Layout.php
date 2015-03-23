<?php
namespace Gantry\Component\Layout;

use Gantry\Component\Filesystem\Folder;
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

    protected $name;
    protected $exists;
    protected $items;
    protected $references;
    protected $sections;
    protected $particles;

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

    public static function preset($name)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $filename = $locator->findResource("gantry-layouts://{$name}.yaml");

        if (!$filename) {
            throw new \RuntimeException('Preset not found', 404);
        }

        return LayoutReader::read($filename);
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
     * @param string $name
     * @param array $items
     */
    public function __construct($name, array $items = null)
    {
        $this->name = $name;
        $this->items = (array) $items;
        $this->exists = $items !== null;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->exists;
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
     * @return array
     */
    public function sections()
    {
        if (!isset($this->references)) {
            $this->initReferences();
        }

        return $this->sections;
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

    /**
     * @param array $items
     */
    protected function initReferences(array $items = null)
    {
        if ($items === null) {
            $items = $this->items;
            $this->references = [];
            $this->sections = [];
        }

        foreach ($items as $item) {
            if (is_object($item)) {
                if (isset($item->id)) {
                    $this->references[$item->id] = $item;
                }
                if ($item->type == 'section') {
                    $this->sections[$item->subtype] = $item;
                } elseif ($item->type == 'non-visible') {
                    $this->sections[$item->type] = $item;
                }
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
    protected static function load($name)
    {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        $layout = null;
        $filename = $locator("gantry-config://{$name}/layout.yaml") ?: $locator("gantry-layouts://{$name}.yaml");

        if ($filename) {
            $layout = LayoutReader::read($filename);
        }

        return new static($name, $layout);
    }
}
