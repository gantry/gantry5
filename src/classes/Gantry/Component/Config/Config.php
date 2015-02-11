<?php
namespace Gantry\Component\Config;

use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\ExportInterface;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccessWithGetters;
use RocketTheme\Toolbox\Blueprints\Blueprints;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 */
class Config implements \ArrayAccess, ExportInterface
{
    use NestedArrayAccessWithGetters, Export;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var Blueprints|callable
     */
    protected $blueprints;

    /**
     * Constructor to initialize array.
     *
     * @param  array  $items  Initial items inside the iterator.
     * @param  callable $blueprints  Function to load Blueprints for the configuration.
     */
    public function __construct(array $items, callable $blueprints = null)
    {
        $this->items = $items;
        $this->blueprints = $blueprints;
    }

    /**
     * Join nested values together by using blueprints.
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $value      Value to be joined.
     * @param string  $separator  Separator, defaults to '.'
     */
    public function join($name, $value, $separator = '.')
    {
        $old = $this->get($name, null, $separator);
        if ($old !== null) {
            $value = $this->blueprints()->mergeData($old, $value, $name, $separator);
        }

        $this->set($name, $value, $separator);
    }

    /**
     * Set default values by using blueprints.
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $value      Value to be joined.
     * @param string  $separator  Separator, defaults to '.'
     */
    public function joinDefaults($name, $value, $separator = '.')
    {
        $old = $this->get($name, null, $separator);
        if ($old !== null) {
            $value = $this->blueprints()->mergeData($value, $old, $name, $separator);
        }

        $this->set($name, $value, $separator);
    }


    /**
     * Merge two configurations together.
     *
     * @param array $data
     * @return void
     */
    public function merge(array $data)
    {
        $this->items = $this->blueprints()->mergeData($this->items, $data);
    }

    /**
     * Set default values to the configuration if variables were not set.
     *
     * @param array $data
     * @return void
     */
    public function setDefaults(array $data)
    {
        $this->items = $this->blueprints()->mergeData($data, $this->items);
    }

    /**
     * Make a flat list from the configuration.
     *
     * @param string $name      Dot separated path to the requested value.
     * @param string $separator Separator, defaults to '.'
     * @return array
     */
    public function flatten($name = null, $separator = '.')
    {
        $element = $name ? $this->offsetGet($name) : $this->items;

        if (!is_array($element)) {
            return [$name, $element];
        }


        return $this->flattenNested('', $element, $separator);
    }

    /**
     * @param $name
     * @param $element
     * @param $separator
     * @return array
     * @internal
     */
    protected function flattenNested($name, &$element, $separator)
    {
        $list = [];
        foreach ($element as $key => $value) {
            $new = $name ? $name . $separator . $key : $key;
            if (!is_array($value) || empty($value)) {
                $list[$new] = $value;
            } else {
                $list += $this->flattenNested($new, $value, $separator);
            }
        }

        return $list;
    }

    /**
     * Return blueprints.
     *
     * @return Blueprints
     */
    public function blueprints()
    {
        if (!$this->blueprints){
            $this->blueprints = new Blueprints;
        } elseif (is_callable($this->blueprints)) {
            // Lazy load blueprints.
            $blueprints = $this->blueprints;
            $this->blueprints = $blueprints();
        }
        return $this->blueprints;
    }
}
