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

use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\Blueprints\BlueprintSchema as BlueprintSchemaBase;

/**
 * Blueprint schema handles the internal logic of blueprints.
 *
 * @author RocketTheme
 * @license MIT
 */
class BlueprintSchema extends BlueprintSchemaBase
{
    protected $configuration;

    protected $ignoreFormKeys = [
        'title' => true,
        'help' => true,
        'placeholder' => true,
        'fields' => true
    ];

    protected $types = [
        'container.set' => [
            'input@' => false
        ],
        'container.tabs' => [
            'input@' => false
        ],
        'separator.note' => [
            'input@' => false
        ],
        'separator.separator' => [
            'input@' => false
        ],
        'key' => [
            'input@' => false
        ],
        'collection.list' => [
            'array' => true
        ]
    ];

    /**
     * Constructor.
     *
     * @param array $serialized  Serialized content if available.
     */
    public function __construct($serialized = null)
    {
        parent::__construct($serialized);

        $this->configuration = new Config(isset($serialized['configuration']) ? $serialized['configuration'] : []);
    }

    /**
     * Convert object into an array.
     *
     * @return array
     */
    public function getState()
    {
        return parent::getState() + ['configuration' => $this->configuration->toArray()];
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
        return array_merge_recursive($this->configuration->toArray(), $this->buildDefaults($this->nested));
    }

    /**
     * Embed an array to the blueprint.
     *
     * @param $name
     * @param array $value
     * @param string $separator
     * @param bool $merge   Merge fields instead replacing them.
     * @return $this
     */
    public function embed($name, array $value, $separator = '.', $merge = false)
    {
        parent::embed($name, $value, $separator, $merge);

        if (isset($value['configuration'])) {
            $this->configuration->set($name, $value['configuration'], $separator);
        }

        return $this;
    }

    /**
     * Validate data against blueprints.
     *
     * @param  array $data
     * @throws \RuntimeException
     */
    public function validate(array $data)
    {
        try {
            $messages = $this->validateArray($data, $this->nested);

        } catch (\RuntimeException $e) {
            throw (new ValidationException($e->getMessage(), $e->getCode(), $e))->setMessages();
        }

        if (!empty($messages)) {
            throw (new ValidationException())->setMessages($messages);
        }
    }

    /**
     * Filter data by using blueprints.
     *
     * @param  array $data
     * @return array
     */
    public function filter(array $data)
    {
        return $this->filterArray($data, $this->nested);
    }

    /**
     * @param array $data
     * @param array $rules
     * @returns array
     * @throws \RuntimeException
     * @internal
     */
    protected function validateArray(array $data, array $rules)
    {
        $messages = $this->checkRequired($data, $rules);

        foreach ($data as $key => $field) {
            $val = isset($rules[$key]) ? $rules[$key] : (isset($rules['*']) ? $rules['*'] : null);
            $rule = is_string($val) ? $this->items[$val] : null;

            if ($rule) {
                // Item has been defined in blueprints.
                $messages += Validation::validate($field, $rule);
            } elseif (is_array($field) && is_array($val)) {
                // Array has been defined in blueprints.
                $messages += $this->validateArray($field, $val);
            } elseif (isset($rules['validation']) && $rules['validation'] == 'strict') {
                // Undefined/extra item.
                throw new \RuntimeException(sprintf('%s is not defined in blueprints', $key));
            }
        }

        return $messages;
    }

    /**
     * @param array $data
     * @param array $rules
     * @return array
     * @internal
     */
    protected function filterArray(array $data, array $rules)
    {
        $results = array();
        foreach ($data as $key => $field) {
            $val = isset($rules[$key]) ? $rules[$key] : (isset($rules['*']) ? $rules['*'] : null);
            $rule = is_string($val) ? $this->items[$val] : null;

            if ($rule) {
                // Item has been defined in blueprints.
                $field = Validation::filter($field, $rule);
            } elseif (is_array($field) && is_array($val)) {
                // Array has been defined in blueprints.
                $field = $this->filterArray($field, $val);
            } elseif (isset($rules['validation']) && $rules['validation'] == 'strict') {
                $field = null;
            }

            if (isset($field) && (!is_array($field) || !empty($field))) {
                $results[$key] = $field;
            }
        }

        return $results;
    }

    /**
     * @param array $data
     * @param array $fields
     * @return array
     */
    protected function checkRequired(array $data, array $fields)
    {
        $messages = [];

        foreach ($fields as $name => $field) {
            if (!is_string($field)) {
                continue;
            }
            $field = $this->items[$field];
            if (isset($field['validate']['required'])
                && $field['validate']['required'] === true
                && !isset($data[$name])) {
                $value = isset($field['label']) ? $field['label'] : $field['name'];
                // TODO: translate
                $message  = sprintf("Please fill up required field '%s'.", $value);
                $messages[$field['name']][] = $message;
            }
        }

        return $messages;
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
}
