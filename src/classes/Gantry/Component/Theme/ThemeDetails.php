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

namespace Gantry\Component\Theme;

use Gantry\Component\Config\BlueprintSchema;
use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\CompiledTheme;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Streams;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccessWithGetters;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class ThemeDetails
 * @package Gantry\Component\Theme
 */
class ThemeDetails implements \ArrayAccess
{
    use NestedArrayAccessWithGetters, Export;

    static protected $instances = [];

    protected $items;
    protected $parent;
    protected $blueprints;

    /**
     * @param string $theme
     * @return static
     */
    public static function instance($theme)
    {
        if (!isset(static::$instances[$theme])) {
            static::$instances[$theme] = new static($theme);
        }

        return static::$instances[$theme];
    }

    /**
     * Create new theme details.
     *
     * @param string $theme
     */
    public function __construct($theme)
    {
        // Load gantry/theme.yaml file.
        $this->loadInitialDetails($theme);

        // Initialize parent theme.
        $this->parent();

        // Add stream for this theme.
        $this->addStream();

        // Load compiled theme details.
        $this->loadCompiledDetails();
    }

    /**
     * Get parent theme details if theme has a parent.
     *
     * @return ThemeDetails|null
     * @throws \RuntimeException
     */
    public function parent()
    {
        $parent = $this->offsetGet('parent');

        if (!$this->parent && $parent) {
            try {
                $this->parent = static::instance($parent);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException(sprintf('Parent theme %s not found', $parent), 404);
            }
        }

        return $this->parent;
    }

    /**
     * Get all possible paths to the theme.
     *
     * @return array
     */
    public function getPaths($overrides = true)
    {
        $paths = array_merge(
            $overrides ? (array) $this->get('theme.setup.overrides', 'gantry-theme://custom') : [],
            ['gantry-theme://'],
            (array) $this->get('theme.setup.base', 'gantry-theme://common')
        );

        $parent = $this->offsetGet('parent');
        if ($parent) {
            // Stream needs to be valid URL.
            $streamName = 'gantry-themes-' . preg_replace('|[^a-z\d+.-]|ui', '-', $parent);
            $paths[] = "{$streamName}://";
        }

        return $this->parsePaths($paths);
    }

    /**
     * Convert theme path into stream URI.
     *
     * @param string $path
     * @return string
     */
    public function getUrl($path)
    {
        $uri = (string) $this->offsetGet($path);

        if (strpos($uri, 'gantry-theme://') === 0) {
            list (, $uri) = explode('://', $uri, 2);
        }
        if (!strpos($uri, '://')) {
            $name = $this->offsetGet('name');

            // Stream needs to be valid URL.
            $streamName = 'gantry-themes-' . preg_replace('|[^a-z\d+.-]|ui', '-', $name);
            $uri = "{$streamName}://{$uri}";
        }

        return $uri;
    }

    /**
     * Turn list of theme paths to be universal, so they can be used outside of the theme.
     *
     * @param array $items
     * @return array
     */
    public function parsePaths(array $items)
    {
        foreach ($items as &$item) {
            $item = $this->parsePath($item);
        }

        return $items;
    }

    /**
     * Convert theme paths to be universal, so they can be used outside of the theme.
     *
     * @param string $path
     * @return string
     */
    public function parsePath($path)
    {
        if (strpos($path, 'gantry-theme://') === 0) {
            list (, $path) = explode('://', $path, 2);
        }
        if (!strpos($path, '://')) {
            $name = $this->offsetGet('name');
            $path = $path ? "gantry-themes://{$name}/{$path}" : "gantry-themes://{$name}";
        }

        return $path;
    }

    /**
     * @return BlueprintSchema
     */
    public function blueprints()
    {
        if (!$this->blueprints) {
            $gantry = Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $cache = $locator->findResource("gantry-cache://{$this->name}/compiled/blueprints", true, true);
            $paths = $locator->findResources('gantry-theme://blueprints/theme');

            $files = (new ConfigFileFinder)->locateFiles($paths);
            $config = new CompiledBlueprints($cache, $files);

            $this->blueprints = $config->load();
        }

        return $this->blueprints;
    }

    public function getStreamName()
    {
        return 'gantry-themes-' . preg_replace('|[^a-z\d+.-]|ui', '-', $this->offsetGet('name'));
    }

    /**
     * @return string|null
     * @deprecated 5.1.5
     */
    public function getParent()
    {
        return $this->offsetGet('parent');
    }

    protected function addStream()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        // Add theme stream.
        $streamName = $this->getStreamName();
        if (!$locator->schemeExists($streamName)) {
            /** @var Streams $streams */
            $streams = $gantry['streams'];

            $streams->add([$streamName => ['paths' => $this->getPaths(false)]]);
        }
    }

    protected function updateInitialDetails(array $list)
    {
        if (isset($list['details'])) {
            $new = [];

            // Convert old file format into the new one.
            $new['theme']['name'] = isset($list['details']['name']) ? $list['details']['name'] : '';
            $new['theme']['version'] = isset($list['details']['version']) ? $list['details']['version'] : '';
            $new['theme']['date'] = isset($list['details']['date']) ? $list['details']['date'] : '';
            $new['theme']['gantry'] = isset($list['configuration']['gantry']) ? $list['configuration']['gantry'] : [];
            $new['theme']['setup'] = isset($list['configuration']['theme']) ? $list['configuration']['theme'] : [];
            $new['dependencies'] = isset($list['configuration']['dependencies']) ? $list['configuration']['dependencies'] : [];

            unset($new['details']['name']);
            unset($new['details']['version']);
            unset($new['details']['date']);
            unset($new['configuration']['gantry']);
            unset($new['configuration']['theme']);
            unset($new['configuration']['dependencies']);

            foreach ($list as $key => $value) {
                $new[$key] = $value;
            }

            return $new;
        }

        return ['theme' => $list];
    }

    protected function loadInitialDetails($theme)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $filename = $locator->findResource("gantry-themes://{$theme}/gantry/theme.yaml");
        if (!$filename) {
            throw new \RuntimeException(sprintf('Gantry 5 Theme %s not found', $theme), 404);
        }

        $cache = $locator->findResource("gantry-cache://{$theme}/compiled/yaml", true, true);

        $file = CompiledYamlFile::instance($filename);
        $this->items = $this->updateInitialDetails($file->setCachePath($cache)->content());
        $file->free();

        $parent = $this->get('theme.setup.parent', $theme);
        $parent = $parent != $theme ? $parent : null;

        $this->offsetSet('name', $theme);
        $this->offsetSet('parent', $parent);
    }

    protected function loadCompiledDetails()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $cache = $locator->findResource("gantry-cache://{$this->name}/compiled/theme", true, true);

        // Generate lookup paths for the theme details.
        $paths = [];
        foreach ($this->getPaths() as $path) {
            $found = $locator->findResources($path . '/gantry');
            if (!empty($found)) {
                $paths = array_merge($paths, $found);
            }
        }

        // Locate all theme detail files to be compiled.
        $files = (new ConfigFileFinder)->locateFiles($paths);

        $self = $this;
        $config = new CompiledTheme($cache, $files);
        $config->setBlueprints(function() use ($self) {
            return $self->blueprints();
        });

        $this->items = $config->load($this->items)->toArray();
    }
}
