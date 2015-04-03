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

/**
 * Read layout from simplified yaml file.
 */
class LayoutReader
{
    protected static $scopes = [0 => 'grid', 1 => 'block'];

    /**
     * Make layout from array data.
     *
     * @param array $data
     * @return array
     */
    public static function data(array $data)
    {
        // Check if we have pre-saved configuration.
        if (isset($data['children'])) {
            $result = self::object($data['children']);

            $last = end($result);
            if ($last->type !== 'non-visible') {
                $result[] = self::parse('non-visible', [], 0);
            }

            return $result;
        }

        // We have user entered file; let's build the layout.

        if (!isset($data['non-visible'])) {
            $data['non-visible'] = [];
        }

        $result = [];
        foreach ($data as $field => $params) {
            $child = self::parse($field, $params, 0);
            unset($child->size);

            $result[] = $child;
        }

        return $result;
    }

    /**
     * Read layout from yaml file and return parsed version of it.
     *
     * @param string $file
     * @return array
     */
    public static function read($file)
    {
        if (!$file) {
            return [];
        }

        $file = CompiledYamlFile::instance($file);

        return self::data((array) $file->content());
    }

    protected static function object(array $items)
    {
        foreach ($items as &$item) {
            $item = (object) $item;
            if (isset($item->attributes) && !is_object($item->attributes)) {
                $item->attributes = (object) $item->attributes;
            }
            if (!empty($item->children)) {
                $item->children = self::object($item->children);
            }
        }

        return $items;
    }

    /**
     * @param int|string $field
     * @param array $content
     * @param int $scope
     * @return array
     */
    protected static function parse($field, $content, $scope)
    {
        if (is_numeric($field))  {
            // Row or block
            $result = (object) ['id' => static::id(), 'type' => self::$scopes[$scope], 'attributes' => (object) []];
            $scope = ($scope + 1) % 2;
        } elseif ($field == 'container') {
            // Container
            $result = (object) ['id' => static::id(), 'type' => $field, 'attributes' => (object) []];
        } else {
            // Section
            $list = explode(' ', $field, 2);
            $field = array_shift($list);
            $size = ((float) array_shift($list)) ?: null;

            $result = (object) [
                'id' => static::id(),
                'type' => ($field == 'non-visible' ? $field : 'section'),
                'subtype' => $field,
                'title' => ucfirst($field),
                'attributes' => (object) [],
                'children' => []
            ];

            if ($size) {
                $result->size = $size;
            }
        }

        foreach ($content as $child => $params) {
            if (is_array($params)) {
                $child = self::parse($child, $params, $scope);
            } else {
                $child = self::resolve($params, $scope);
            }
            if (!empty($child->size)) {
                $result->attributes->size = $child->size;
            }
            unset($child->size);
            $result->children[] = $child;
        }

        return (object) $result;
    }

    /**
     * @param string $field
     * @param int $scope
     * @return array
     */
    protected static function resolve($field, $scope)
    {
        $list = explode(' ', $field, 2);
        $list2 = explode('-', array_shift($list), 2);
        $size = ((float) array_shift($list)) ?: null;
        $type = array_shift($list2);
        $subtype = array_shift($list2);
        $title = ucfirst($subtype ?: $type);

        $attributes = new \stdClass;

        $attributes->enabled = 1;

        if ($subtype && $type == 'position') {
            $attributes->key = $subtype;
        }

        if ($type == 'particle') {
            $result = ['id' => static::id(), 'title' => $title, 'type' => $type, 'subtype' => $subtype, 'attributes' => $attributes];
        } else {
            $result = ['id' => static::id(), 'title' => $title, 'type' => $type, 'attributes' => $attributes];
        }

        $result = (object) $result;

        if ($scope > 1) {
            if ($size) {
                $result->attributes->size = $size;
            }
            return $result;
        }
        if ($scope <= 1) {
            $result = (object) ['id' => static::id(), 'type' => 'block', 'children' => [$result], 'attributes' => new \stdClass];
            if ($size) {
                $result->attributes->size = $size;
            }
        }
        if ($scope == 0) {
            $result = (object) ['id' => static::id(), 'type' => 'grid', 'children' => [$result]];
        }

        return $result;
    }

    protected static function id()
    {
        // TODO: improve
        $key = md5(rand());

        $args = str_split($key, 4);
        array_unshift($args, '%s%s-%s-%s-%s-%s%s%s');

        return call_user_func_array('sprintf', $args);
    }
}
