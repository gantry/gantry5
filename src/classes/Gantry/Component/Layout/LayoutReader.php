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
            $preset = isset($data['preset']) && is_array($data['preset']) ? $data['preset'] : [];

            $result = self::object($data['children']);

            $invisible = [
                'offcanvas' => self::parse('offcanvas', [], 0),
                'atoms' => self::parse('atoms', [], 0)
            ];
            foreach ($result as $key => &$item) {
                // FIXME: remove before release
                if ($item->type == 'non-visible') {
                    $item->type = 'atoms';
                    $item->attributes->name = 'Atoms Section';
                }

                if (isset($invisible[$item->type])) {
                    $invisible[$item->type] = $item;
                    unset($result[$key]);
                }
            }

            $result += $invisible;

            // Make sure that all preset values are set by defining defaults.
            $preset += [
                'name' => '',
                'image' => 'gantry-admin://images/layouts/default.png'
            ];

            return ['preset' => $preset] + array_values($result);
        }

        // Check if we have preset.
        $preset = [];
        if (isset($data['preset']) && is_array($data['preset']) && isset($data['layout']) && is_array($data['layout'])) {
            $preset = $data['preset'];
            $data = $data['layout'];
        }

        // We have user entered file; let's build the layout.

        // FIXME: remove before release
        if (isset($data['non-visible'])) {
            $data['offcanvas'] = [];
            unset ($data['non-visible']);
        }

        // Two last items are always offcanvas and atoms.
        $invisible = [
            'offcanvas' => isset($data['offcanvas']) ? $data['offcanvas'] : [],
            'atoms' => isset($data['atoms']) ? $data['atoms'] : []
        ];
        unset($data['offcanvas'], $data['atoms']);
        $data += $invisible;

        $result = [];
        foreach ($data as $field => $params) {
            $child = self::parse($field, $params, 0);
            unset($child->size);

            $result[] = $child;
        }

        // Make sure that all values are set by defining defaults.
        $preset += [
            'name' => '',
            'image' => 'gantry-admin://images/layouts/default.png'
        ];
        return ['preset' => $preset] + $result;
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
        $content = (array) $file->content();
        $file->free();

        return self::data($content);
    }

    protected static function object(array $items)
    {
        foreach ($items as &$item) {
            $item = (object) $item;
            if (isset($item->attributes) && (is_array($item->attributes) || is_object($item->attributes))) {
                $item->attributes = (object) $item->attributes;
            } else {
                $item->attributes = (object) [];
            }

            if (!empty($item->children) && is_array($item->children)) {
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
                // TODO: add offcanvas type..
                'type' => (in_array($field, ['atoms', 'offcanvas']) ? $field : 'section'),
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

        if ($type === 'system' && $subtype = 'messages') {
            $subtype = $type . '-' . $subtype;
            $type = 'pagecontent';
            $title = 'System Messages';
        }

        if ($subtype && $type == 'position') {
            $attributes->key = $subtype;
            $subtype = null;
        }

        if ($subtype) {
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
            $result = (object) ['id' => static::id(), 'type' => 'grid', 'children' => [$result], 'attributes' => new \stdClass];
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
