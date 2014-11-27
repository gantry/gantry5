<?php
namespace Gantry\Component\Theme;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ThemeDetails implements \ArrayAccess
{
    use NestedArrayAccess;

    protected $items;

    public function __construct($theme)
    {
        $this->items = CompiledYamlFile::instance("gantry-themes://{$theme}/theme.yaml")->content();
        $this->offsetSet('name', $theme);
    }

    public function getPaths()
    {
        $parent = $this->getParent();

        $paths = (array) $this->offsetGet('config.overrides');
        $paths[] = 'gantry-themes://' . $this->offsetGet('name');
        $paths = array_merge($paths, (array) $this->offsetGet('config.base'));
        if ($parent) {
            $paths[] = "gantry-theme-{$parent}://";
        }

        return $this->parsePaths($paths);
    }

    public function getParent()
    {
        $parent = (string) $this->offsetGet('config.parent');
        return $parent && $parent != $this->offsetGet('name') ? $parent : false;
    }

    protected function parsePaths(array $items)
    {
        $name = $this->offsetGet('name');

        foreach ($items as &$item) {
            if (!strpos($item, '://')) {
                $item = "gantry-themes://{$name}/{$item}";
            }
        }

        return $items;
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
