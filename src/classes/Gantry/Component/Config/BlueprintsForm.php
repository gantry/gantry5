<?php
namespace Gantry\Component\Config;

use RocketTheme\Toolbox\ArrayTraits\Constructor;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\ExportInterface;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccessWithGetters;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 */
class BlueprintsForm implements \ArrayAccess, ExportInterface
{
    use Constructor, NestedArrayAccessWithGetters, Export;

    /**
     * @var array
     */
    protected $items;

    /**
     * Get blueprints by using dot notation for nested arrays/objects.
     *
     * @example $value = $this->resolve('this.is.my.nested.variable');
     * returns ['this.is.my', 'nested.variable']
     *
     * @param array  $path
     * @param string  $separator
     * @return array
     */
    public function resolve(array $path, $separator = '.')
    {
        $fields = false;
        $parts = [];
        $current = $this['form.fields'];

        while (($field = current($path)) !== null) {
            if (!$fields && isset($current['fields'])) {
                if (!empty($current['array'])) {
                    break;
                }

                $current = $current['fields'];
                $fields = true;

            } elseif (isset($current[$field])) {
                $parts[] = array_shift($path);
                $current = $current[$field];
                $fields = false;

            } else {
                return [null, null, null];
            }
        }

        return [$current, $parts, $path ? implode($separator, $path) : null];
    }
}
