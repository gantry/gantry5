<?php
namespace Gantry\Component\Layout;

use RocketTheme\Toolbox\File\YamlFile;

/**
 * Read layout from simplified yaml file.
 */
class LayoutReader
{
    protected static $scopes = [0 => 'grid', 1 => 'block'];

    /**
     * Read layout from yaml file and return parsed version of it.
     *
     * @param string $file
     * @return array
     */
    public static function read($file) {
        if (!$file) {
            return [];
        }

        $file = YamlFile::instance($file);
        $content = (array) $file->content();

        $result = [];
        foreach ($content as $field => $params) {
            $child = self::parse($field, $params, 0);
            unset($child->size);

            $result[] = $child;
        }

        return $result;
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
            $size = ((int) array_shift($list)) ?: null;

            $result = (object) [
                'id' => static::id(),
                'type' => ($field == 'non-visible' ? $field : 'section'),
                'title' => ucfirst($field),
                'attributes' => (object) [
                    'type' => $field,
                    'id' => $field
                ],
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
        $size = ((int) array_shift($list)) ?: null;
        $type = array_shift($list2);
        $subtype = array_shift($list2);
        $title = ucfirst($subtype ?: $type);

        $attributes = new \stdClass;

        if ($subtype) {
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
