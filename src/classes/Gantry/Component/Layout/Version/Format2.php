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

namespace Gantry\Component\Layout\Version;

/**
 * Read layout from simplified yaml file.
 */
class Format2
{
    protected $scopes = [0 => 'grid', 1 => 'block'];
    protected $section = ['grid', 'block', 'offcanvas', 'div'];
    protected $structure = ['div', 'section', 'aside', 'nav', 'article', 'header', 'footer'];

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function load()
    {
        $data = &$this->data;

        $result = [];
        foreach ($data['layout'] as $field => &$params) {
            if (!is_array($params)) {
                $params = [];
            }
            $child = $this->parse($field, $params);
            unset($child->size);

            $result[] = $child;
        }

        return ['preset' => $data['preset']] + $result;
    }

    /**
     * @param int|string $field
     * @param array $content
     * @param int $scope
     * @return array
     */
    protected function parse($field, array &$content, $scope = 0)
    {
        if (is_numeric($field))  {
            // Row or block
            $result = (object) ['id' => null, 'type' => $this->scopes[$scope], 'subtype' => false, 'attributes' => (object) []];
            $scope = ($scope + 1) % 2;

        } else {
            // Extract: "[section-id] [size]".
            $list = explode(' ', $field, 2);
            $section_id = array_shift($list);
            $size = ((float) array_shift($list)) ?: null;

            // Extract section id if it exists: "[section]-[id]".
            $list = explode('-', $section_id, 2);

            // Get section and its type.
            $section = reset($list);
            $type = (in_array($section, $this->section) ? $section : 'section');

            if ($type == 'grid') {
                $scope = 1;
            }
            // Extract id.
            if ($type == 'div' || ($type == 'section' && in_array($section, $this->structure))) {
                $id = array_pop($list);
            } else {
                $id = $section_id;
            }

            // Build object.
            $result = isset($this->data['structure'][$section_id]) ? (array) $this->data['structure'][$section_id] : [];
            $result += [
                'id' => 'g-' . $id,
                'type' => $type,
                'subtype' => $type !== $section ? $section : false,
                'title' => ucfirst($id),
                'attributes' => (object) []
            ];
            $result = (object) $result;

            if ($size) {
                $result->size = $size;
            }
        }

        if (!empty($content)) {
            $result->children = [];
            foreach ($content as $child => &$params) {
                if (is_array($params)) {
                    $child = $this->parse($child, $params, $scope);
                } else {
                    $child = $this->resolve($params, $scope);
                }
                if (!empty($child->size)) {
                    $result->attributes->size = $child->size;
                }
                unset($child->size);
                $result->children[] = $child;
            }
        }

        return $result;
    }

    /**
     * @param string $field
     * @param int $scope
     * @return array
     */
    protected function resolve($field, $scope)
    {
        // Extract: "[type-subtype] [size]".
        $list = explode(' ', $field, 2);
        $type_subtype = array_shift($list);
        $size = ((float) array_shift($list)) ?: null;

        // Extract sub-type if it exists: "[type]-[subtype]".
        $list = explode('-', $type_subtype, 2);

        // Get type and subtype.
        $type = reset($list);
        if (in_array($type, ['system', 'position', 'particle', 'atom'])) {
            $subtype = array_pop($list);
        } else {
            $type = 'particle';
            $subtype = $type_subtype;
        }

        $title = ucfirst($subtype ?: $type);

        if ($type === 'system' && $subtype === 'messages') {
            $subtype = 'system-messages';
            $type = 'pagecontent';
            $title = 'System Messages';
        }
        if ($type === 'system' && $subtype === 'content') {
            $subtype = 'pagecontent';
            $type = 'pagecontent';
            $title = 'Page Content';
        }

        if ($type === 'position') {
            if (is_numeric($subtype)) {
                $subtype = "{$type}-{$subtype}";
                $title = ucfirst($subtype);
            }
            $key = $subtype;
            $subtype = false;
        }

        $result = isset($this->data['content'][$type_subtype]) ? (array) $this->data['content'][$type_subtype] : [];
        $result += ['id' => $this->id(), 'title' => $title, 'type' => $type, 'subtype' => $subtype, 'attributes' => []];

        $result['attributes'] = (object) ($result['attributes'] + ['enabled' => 1]);
        $result = (object) $result;

        if (isset($key)) {
            $result->attributes->key = $key;
        }
        if ($scope > 1) {
            if ($size) {
                $result->attributes->size = $size;
            }
        }
        if ($scope <= 1) {
            $result = (object) ['id' => $this->id(), 'type' => 'block', 'subtype' => false, 'children' => [$result], 'attributes' => new \stdClass];
            if ($size) {
                $result->attributes->size = $size;
            }
        }
        if ($scope == 0) {
            $result = (object) ['id' => $this->id(), 'type' => 'grid', 'subtype' => false, 'children' => [$result], 'attributes' => new \stdClass];
        }

        return $result;
    }

    protected function id()
    {
        // TODO: improve
        $key = md5(rand());

        $args = str_split($key, 4);
        array_unshift($args, '%s%s-%s-%s-%s-%s%s%s');

        return call_user_func_array('sprintf', $args);
    }
}
