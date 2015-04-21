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

namespace Gantry\Component\Theme;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ThemeDetails implements \ArrayAccess
{
    use NestedArrayAccess, Export;

    protected $items;

    public function __construct($theme)
    {
        $gantry = Gantry::instance();
        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $this->items = CompiledYamlFile::instance($locator("gantry-themes://{$theme}/gantry/theme.yaml"))->content();
        $this->offsetSet('name', $theme);

        $parent = $this->offsetGet('configuration.theme.parent') ?: $theme;

        $this->offsetSet('parent', $theme !== $parent ? $parent : null);
    }

    public function getPaths()
    {
        $paths = array_merge(
            ['gantry-theme://'],
            (array) $this->get('configuration.theme.base', 'gantry-theme://common')
        );

        $parent = $this->getParent();
        if ($parent) {
            $paths[] = "gantry-themes-{$parent}://";
        }

        return $this->parsePaths($paths);
    }

    public function getUrl($path)
    {
        $uri = (string) $this->offsetGet($path);

        if (strpos($uri, 'gantry-theme://') === 0) {
            list ($scheme, $uri) = explode('://', $uri, 2);
        }
        if (!strpos($uri, '://')) {
            $name = $this->offsetGet('name');
            $uri = "gantry-themes-{$name}://{$uri}";
        }

        return $uri;
    }

    public function getParent()
    {
        $parent = (string) $this->offsetGet('configuration.theme.parent');
        return $parent && $parent != $this->offsetGet('name') ? $parent : null;
    }

    protected function parsePaths(array $items)
    {
        foreach ($items as &$item) {
            $item = $this->parsePath($item);
        }

        return $items;
    }

    public function parsePath($path)
    {
        if (strpos($path, 'gantry-theme://') === 0) {
            list ($scheme, $path) = explode('://', $path, 2);
        }
        if (!strpos($path, '://')) {
            $name = $this->offsetGet('name');
            $path = "gantry-themes://{$name}/{$path}";
        }

        return $path;
    }

    /**
     * Magic setter method
     *
     * @param mixed $offset Asset name value
     * @param mixed $value  Asset value
     */
    public function __set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    /**
     * Magic getter method
     *
     * @param  mixed $offset Asset name value
     * @return mixed         Asset value
     */
    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    /**
     * Magic method to determine if the attribute is set
     *
     * @param  mixed   $offset Asset name value
     * @return boolean         True if the value is set
     */
    public function __isset($offset)
    {
        return $this->offsetExists($offset);
    }

    /**
     * Magic method to unset the attribute
     *
     * @param mixed $offset The name value to unset
     */
    public function __unset($offset)
    {
        $this->offsetUnset($offset);
    }
}
