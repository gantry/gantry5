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
        $result = [null, null, null];

        while (($field = current($path)) !== null) {
            if (!$fields && isset($current['fields'])) {
                if (!empty($current['array'])) {
                    $result = [$current, $parts, $path ? implode($separator, $path) : null];
                    // Skip item offset.
                    $parts[] = array_shift($path);
                }

                $current = $current['fields'];
                $fields = true;

            } elseif (isset($current[$field])) {
                $parts[] = array_shift($path);
                $current = $current[$field];
                $fields = false;

            } elseif (isset($current['.' . $field])) {
                $parts[] = array_shift($path);
                $current = $current['.' . $field];
                $fields = false;

            } else {
                // properly loop through nested containers to find deep matching fields
                $inner_fields = null;
                foreach($current as $field) {
                    $type = isset($field['type']) ? $field['type'] : '-undefined-';
                    $container = (0 === strpos($type, 'container.')) || $type === '-undefined-';
                    $fields = isset($field['fields']);
                    $container_fields = [];

                    // if the field has no type, it most certainly is a container
                    if ($type === '-undefined-') {
                        // loop through all the container inner fields and reduce to a flat blueprint
                        $current_fields = isset($current['fields']) ? $current['fields'] : $current;
                        foreach ($current_fields as $container_field) {
                            if (isset($container_field['fields'])) {
                                $container_fields[] = $container_field['fields'];
                            }
                        }

                        // any container structural data can be discarded, flatten
                        $field = array_reduce($container_fields, 'array_merge', []);
                    }

                    if ($container && is_array($field)) {
                        $inner_fields = $field;
                        break;
                    }
                }

                // if a deep matching field is found, set it to current and continue cycling through
                if ($inner_fields) {
                    $current = $inner_fields;
                    continue;
                }

                // nothing found, exit the loop
                break;
            }
        }

        return $result;
    }
}
