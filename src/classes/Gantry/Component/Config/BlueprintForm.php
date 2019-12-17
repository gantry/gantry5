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

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\Blueprints\BlueprintForm as BaseBlueprintForm;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 */
class BlueprintForm extends BaseBlueprintForm
{
    /**
     * @var string
     */
    protected $context = 'gantry-blueprints://';

    /**
     * @var BlueprintSchema
     */
    protected $schema;

    /**
     * @param string $filename
     * @param string $context
     * @return BlueprintForm
     */
    public static function instance($filename, $context = null)
    {
        /** @var BlueprintForm $instance */
        $instance = new static($filename);
        if ($context) {
            $instance->setContext($context);
        }

        return $instance->load()->init();
    }

    /**
     * Get nested structure containing default values defined in the blueprints.
     *
     * Fields without default value are ignored in the list.
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->schema()->getDefaults();
    }

    /**
     * Merge two arrays by using blueprints.
     *
     * @param  array $data1
     * @param  array $data2
     * @param  string $name         Optional
     * @param  string $separator    Optional
     * @return array
     */
    public function mergeData(array $data1, array $data2, $name = null, $separator = '.')
    {
        return $this->schema()->mergeData($data1, $data2, $name, $separator);
    }

    /**
     * Return data fields that do not exist in blueprints.
     *
     * @param  array  $data
     * @param  string $prefix
     * @return array
     */
    public function extra(array $data, $prefix = '')
    {
        return $this->schema()->extra($data, $prefix);
    }

    /**
     * Validate data against blueprints.
     *
     * @param  array $data
     * @throws \RuntimeException
     */
    public function validate(array $data)
    {
        $this->schema()->validate($data);
    }

    /**
     * Filter data by using blueprints.
     *
     * @param  array $data
     * @return array
     */
    public function filter(array $data)
    {
        return $this->schema()->filter($data);
    }

    /**
     * @return BlueprintSchema
     */
    public function schema()
    {
        if (!isset($this->schema)) {
            $this->schema = new BlueprintSchema;
            $this->schema->embed('', $this->items);
            $this->schema->init();
        }

        return $this->schema;
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function loadFile($filename)
    {
        $file = CompiledYamlFile::instance($filename);
        $content = $file->content();
        $file->free();

        return $content;
    }

    /**
     * @param string|array $path
     * @param string $context
     * @return array
     */
    protected function getFiles($path, $context = null)
    {
        if (is_string($path) && !strpos($path, '://')) {
            // Resolve filename.
            if (isset($this->overrides[$path])) {
                $path = $this->overrides[$path];
            } else {
                if ($context === null) {
                    $context = $this->context;
                }
                if ($context && $context[strlen($context)-1] !== '/') {
                    $context .= '/';
                }
                $path = $context . $path;

                if (!preg_match('/\.yaml$/', $path)) {
                    $path .= '.yaml';
                }
            }
        }

        if (is_string($path) && strpos($path, '://')) {
            /** @var UniformResourceLocator $locator */
            $locator = Gantry::instance()['locator'];

            $files = $locator->findResources($path);
        } else {
            $files = (array) $path;
        }

        return $files;
    }

    /**
     * @param array $field
     * @param string $property
     * @param array $call
     */
    protected function dynamicData(array &$field, $property, array &$call)
    {
        $params = $call['params'];

        if (is_array($params)) {
            $function = array_shift($params);
        } else {
            $function = $params;
            $params = [];
        }

        list($o, $f) = preg_split('/::/', $function, 2);
        if (!$f) {
            if (function_exists($o)) {
                $data = call_user_func_array($o, $params);
            }
        } else {
            if (method_exists($o, $f)) {
                $data = call_user_func_array(array($o, $f), $params);
            }
        }

        // If function returns a value,
        if (isset($data)) {
            if (isset($field[$property]) && is_array($field[$property]) && is_array($data)) {
                // Combine field and @data-field together.
                $field[$property] += $data;
            } else {
                // Or create/replace field with @data-field.
                $field[$property] = $data;
            }
        }
    }

    /**
     * @param array $field
     * @param string $property
     * @param array $call
     */
    protected function dynamicConfig(array &$field, $property, array &$call)
    {
        $value = $call['params'];

        $default = isset($field[$property]) ? $field[$property] : null;
        $config = Gantry::instance()['config']->get($value, $default);

        if (!is_null($config)) {
            $field[$property] = $config;
        }
    }

    /**
     * Get blueprints by using slash notation for nested arrays/objects.
     *
     * @param array  $path
     * @param string  $separator
     * @return array
     */
    public function resolve(array $path, $separator = '/')
    {
        $fields = false;
        $parts = [];
        $current = $this['form/fields'];
        $result = [null, null, null];
        $prefix = '';

        while (($field = current($path)) !== false) {
            if (!$fields && isset($current['fields'])) {
                if (!empty($current['array'])) {
                    $result = [$current, $parts, $path ? implode($separator, $path) : null];
                    // Skip item offset.
                    $parts[] = array_shift($path);
                }

                $current = $current['fields'];
                $prefix = '';
                $fields = true;

            } elseif (isset($current[$prefix . $field])) {
                $parts[] = array_shift($path);
                $current = $current[$prefix . $field];
                $prefix = '';
                $fields = false;

            } elseif (isset($current['.' . $prefix . $field])) {
                $parts[] = array_shift($path);
                $current = $current['.' . $prefix . $field];
                $prefix = '';
                $fields = false;

            } elseif ($field && $this->fieldExists($prefix . $field, $current)) {
                $parts[] = array_shift($path);
                $prefix = "{$prefix}{$field}.";
                $fields = false;

            } else {
                // Check if there's a container with the field.
                $current = $this->resolveContainer($current, $prefix, $field);

                // No containers with the field found.
                if (!$current) {
                    break;
                }

                $prefix = '';
                $fields = false;
            }
        }

        if (!$fields && !empty($current['array'])) {
            $result = [$current, $parts, $path ? implode($separator, $path) : null];
        }

        return $result;
    }

    protected function resolveContainer($current, $prefix, $fieldName)
    {
        foreach ($current as $field) {
            $type = isset($field['type']) ? $field['type'] : 'container._implicit';
            $container = (0 === strpos($type, 'container.'));

            if (!$container || !isset($field['fields'])) {
                continue;
            }

            $current_fields = $field['fields'];
            if (isset($current_fields[$prefix . $fieldName]) || isset($current_fields['.' . $prefix . $fieldName])
                || $this->fieldExists($prefix . $fieldName, $current_fields)) {
                return $current_fields;
            }

            $current_fields = $this->resolveContainer($current_fields, $prefix, $fieldName);
            if ($current_fields !== null) {
                return $current_fields;
            }
        }

        return null;
    }

    protected function fieldExists($prefix, $list)
    {
        foreach ($list as $field => $data) {
            $pos = strpos($field, $prefix);
            if ($pos === 0 || ($pos === 1 && $field[0] === '.')) {
                return true;
            }
        }

        return false;
    }
}
