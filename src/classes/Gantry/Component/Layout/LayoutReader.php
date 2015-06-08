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
     * @param bool $array
     * @return array
     */
    public static function data(array $data, $array = true)
    {
        // Check if we have pre-saved configuration.
        if (array_key_exists('children', $data)) {
            $preset = isset($data['preset']) && is_array($data['preset']) ? $data['preset'] : [];

            $result = is_array($data['children']) ? static::object($data['children']) : [];

            $invisible = [
                'offcanvas' => static::parse('offcanvas', [], 0),
                'atoms' => static::parse('atoms', [], 0)
            ];
            foreach ($result as $key => &$item) {
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

            $result = array_values($result);

        } else {

            // Check if we have preset.
            $preset = [];
            if (isset($data['preset']) && is_array($data['preset']) && isset($data['layout']) && is_array($data['layout'])) {
                $preset = $data['preset'];
                $data = $data['layout'];
            }

            // We have user entered file; let's build the layout.

            // Two last items are always offcanvas and atoms.
            $invisible = [
                'offcanvas' => isset($data['offcanvas']) ? $data['offcanvas'] : [],
                'atoms' => isset($data['atoms']) ? $data['atoms'] : []
            ];
            unset($data['offcanvas'], $data['atoms']);
            $data += $invisible;

            $result = [];
            foreach ($data as $field => $params) {
                $child = static::parse($field, (array) $params, 0);
                unset($child->size);

                $result[] = $child;
            }

            // Make sure that all values are set by defining defaults.
            $preset += [
                'name' => '',
                'image' => 'gantry-admin://images/layouts/default.png'
            ];
        }

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

        return static::data($content);
    }

    protected static function object(array $items, $container = true)
    {
        foreach ($items as &$item) {
            $item = (object) $item;

            if (!isset($item->id)) {
                $item->id = static::id();
            }

            // TODO: remove extra fields..
            /*
            if (empty($item->subtype)) {
                unset($item->subtype);
            }

            if (empty($item->title) || $item->title === 'Untitled') {
                unset($item->title);
            }
            */

            if (isset($item->attributes) && (is_array($item->attributes) || is_object($item->attributes))) {
                $item->attributes = (object) $item->attributes;
            } else {
                $item->attributes = (object) [];
            }

            if (!empty($item->children) && is_array($item->children)) {
                $item->children = static::object($item->children, false);
            }
            /*
            else {
                unset($item->children);
            }
            */

            if ($container) {
                static::normalize($item);
            }
        }

        return $items;
    }

    protected static function normalize(&$item)
    {
        if (isset($item->attributes->boxed)) {
            return;
        }

        if (isset($item->children) && count($item->children) === 1) {
            $child = reset($item->children);
            if ($item->type === 'container') {
                // Remove parent container only if the only child is a section.
                if ($child->type === 'section') {
                    $child->attributes->boxed = 1;
                    $item = $child;
                }
            } elseif ($child->type === 'container') {
                // Remove child container.
                $item->attributes->boxed = 0;
                $item->children = $child->children;
            }
        }
        if (!isset($item->attributes->boxed)) {
            $item->attributes->boxed = 0;
        }
    }

    /**
     * @param int|string $field
     * @param array $content
     * @param int $scope
     * @param bool|null $container
     * @return array
     */
    protected static function parse($field, array $content, $scope, $container = true)
    {
        if (is_numeric($field))  {
            // Row or block
            $result = (object) ['id' => static::id(), 'type' => static::$scopes[$scope], 'subtype' => false, 'attributes' => (object) []];
            $scope = ($scope + 1) % 2;
        } elseif (substr($field, 0, 9) == 'container') {
            // Container
            $result = (object) ['id' => static::id(), 'type' => 'container', 'subtype' => false, 'attributes' => (object) []];
            $id = substr($field, 10) ?: null;
            if ($id !== null) {
                $result->attributes->id = $id;
            }
        } else {
            // Section
            $list = explode(' ', $field, 2);
            $field = array_shift($list);
            $size = ((float) array_shift($list)) ?: null;

            $result = (object) [
                'id' => static::id(),
                'type' => (in_array($field, ['atoms', 'offcanvas']) ? $field : 'section'),
                'subtype' => false,
                'title' => ucfirst($field),
                'attributes' => (object) []
            ];

            if ($size) {
                $result->size = $size;
            }
        }

        if (!empty($content)) {
            $result->children = [];
            foreach ($content as $child => $params) {
                if (is_array($params)) {
                    $child = static::parse($child, $params, $scope, false);
                } else {
                    $child = static::resolve($params, $scope);
                }
                if (!empty($child->size)) {
                    $result->attributes->size = $child->size;
                }
                unset($child->size);
                $result->children[] = $child;
            }
        }

        if ($container) {
            static::normalize($result);
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
            $subtype = false;
        }

        if ($subtype) {
            $result = ['id' => static::id(), 'title' => $title, 'type' => $type, 'subtype' => $subtype, 'attributes' => $attributes];
        } else {
            $result = ['id' => static::id(), 'title' => $title, 'type' => $type, 'subtype' => false, 'attributes' => $attributes];
        }

        $result = (object) $result;

        if ($scope > 1) {
            if ($size) {
                $result->attributes->size = $size;
            }
            return $result;
        }
        if ($scope <= 1) {
            $result = (object) ['id' => static::id(), 'type' => 'block', 'subtype' => false, 'children' => [$result], 'attributes' => new \stdClass];
            if ($size) {
                $result->attributes->size = $size;
            }
        }
        if ($scope == 0) {
            $result = (object) ['id' => static::id(), 'type' => 'grid', 'subtype' => false, 'children' => [$result], 'attributes' => new \stdClass];
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
