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

namespace Gantry\Component\Layout\Version;

/**
 * Read layout from simplified yaml file.
 */
class Format2
{
    protected $scopes = [0 => 'grid', 1 => 'block'];
    protected $sections = ['wrapper', 'container', 'section', 'grid', 'block', 'offcanvas'];
    protected $structures = ['div', 'section', 'aside', 'nav', 'article', 'header', 'footer', 'main'];

    protected $data;
    protected $structure;
    protected $content;
    protected $keys;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function load()
    {
        $data = &$this->data;

        // Parse layout.
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
     * @param array $preset
     * @param array $structure
     * @return array
     */
    public function store(array $preset, array $structure)
    {
        $this->structure = [];
        $this->content = [];

        $structure = ['children' => json_decode(json_encode($structure), true)];
        $structure = $this->build($structure);

        $result = [
            'version' => 2,
            'preset' => $preset,
            'layout' => $structure
        ];

        if ($this->structure) {
            $result['structure'] = $this->structure;
        }
        if ($this->content) {
            $result['content'] = $this->content;
        }

        return $result;
    }

    /**
     * @param int|string $field
     * @param array $content
     * @param int $scope
     * @param object $parent
     * @return array
     */
    protected function parse($field, array &$content, $scope = 0, $parent = null)
    {
        if (is_numeric($field)) {
            // Row or block
            $result = (object)['id' => $this->id($this->scopes[$scope]), 'type' => $this->scopes[$scope], 'subtype' => $this->scopes[$scope], 'layout' => true, 'attributes' => (object)[]];
            $scope = ($scope + 1) % 2;

        } else {
            list ($type, $subtype, $id, $size, $section_id, $boxed) = $this->parseSectionString($field);

            if ($type == 'grid') {
                $scope = 1;
            }
            if ($type == 'block') {
                $scope = 0;
            }

            // Build object.
            $result = isset($this->data['structure'][$section_id]) ? (array) $this->data['structure'][$section_id] : [];
            $result += [
                'id' => $section_id,
                'layout' => true,
                'type' => $type,
                'subtype' => $subtype,
                'title' => $this->getTitle($type, $subtype, $id),
                'attributes' => []
            ];
            if (isset($boxed) && !isset($result['attributes']['boxed'])) {
                $result['attributes']['boxed'] = $boxed;
            }
            if ($parent && $parent->type === 'block' && !empty($result['block'])) {
                $parent->attributes = (object) ($result['block'] + (array) $parent->attributes);
            }
            unset ($result['block']);

            $result = (object) $result;
            $result->attributes = (object) $result->attributes;
            if (isset($result->inherit)) {
                $result->inherit = (object) $result->inherit;
            }

            if ($size) {
                $result->size = $size;
            }
            if (($type === 'grid' || $type === 'block') && !isset($result->attributes->id)) {
                $result->attributes->id = $section_id;
            }
        }

        if (!empty($content)) {
            $result->children = [];
            foreach ($content as $child => &$params) {
                if (!$params && !is_array($params)) {
                    $params = [];
                }
                if (is_array($params)) {
                    $child = $this->parse($child, $params, $scope, $result);
                } else {
                    $child = $this->resolve($params, $scope, $result);
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
     * @param object $parent
     * @return array
     */
    protected function resolve($field, $scope, $parent)
    {
        list ($type, $subtype, $id, $size, $content_id) = $this->parseContentString($field);

        $title = $this->getTitle($type, $subtype, $id);

        $result = isset($this->data['content'][$content_id]) ? (array) $this->data['content'][$content_id] : [];
        $result += ['id' => $this->id($type, $subtype, $id), 'title' => $title, 'type' => $type, 'subtype' => $subtype, 'attributes' => []];

        $result['attributes'] = (object) ($result['attributes'] + ['enabled' => 1]);
        if (isset($result['inherit'])) {
            $result['inherit'] = (object) $result['inherit'];
        }

        if (isset($result['block'])) {
            $block = $result['block'];
            unset ($result['block']);
        }

        $result = (object) $result;

        if ($type === 'position' && !isset($result->attributes->key) && !in_array($subtype, ['module', 'widget'])) {
            $result->attributes->key = $id;
        }
        if ($scope > 1) {
            if ($parent->type === 'block' && !empty($block)) {
                $parent->attributes = (object) ($block + (array) $parent->attributes);
            }
            if ($size) {
                $result->attributes->size = $size;
            }
        }
        if ($scope <= 1) {
            $result = (object) ['id' => $this->id('block'), 'type' => 'block', 'subtype' => 'block', 'layout' => true, 'children' => [$result], 'attributes' => new \stdClass];
            if (!empty($block)) {
                $result->attributes = (object) $block;
            }
            if ($size) {
                $result->attributes->size = $size;
            }
        }
        if ($scope == 0) {
            $result = (object) ['id' => $this->id('grid'), 'type' => 'grid', 'subtype' => 'grid', 'layout' => true, 'children' => [$result], 'attributes' => new \stdClass];
        }

        return $result;
    }

    /**
     * @param array $content
     * @return array|null
     */
    protected function build(array &$content)
    {
        $result = [];
        $ctype = isset($content['type']) ? $content['type'] : null;

        if (in_array($ctype, ['grid', 'block'])) {
            if (empty($content['attributes']['id']) || $content['attributes']['id'] === $content['id']) {
                unset ($content['attributes']['id']);
            }
        }
        if ($ctype === 'block') {
            if (empty($content['attributes']['extra'])) {
                unset ($content['attributes']['extra']);
            }
            if (empty($content['attributes']['fixed'])) {
                unset ($content['attributes']['fixed']);
            }
        }
        if ($ctype === 'section') {
            if (empty($content['attributes']['extra'])) {
                unset ($content['attributes']['extra']);
            }
        }

        if (!isset($content['children'])) {
            $content['children'] = [];
        }
        unset ($content['layout']);

        // Clean up all items for saving.
        foreach ($content['children'] as &$child) {
            $size = null;
            $id = $child['id'];
            $type = $child['type'];
            $subtype = $child['subtype'];
            $isSection = in_array($type, $this->sections);

            if (empty($child['inherit']['outline']) || empty($child['inherit']['include'])) {
                unset ($child['inherit']);
            } else {
                foreach ($child['inherit']['include'] as $include) {
                    switch ($include) {
                        case 'attributes':
                            unset($child['attributes']);
                            break;
                        case 'block':
                            if ($ctype === 'block') {
                                // Keep block size and fixed status.
                                $content['attributes'] = array_intersect_key($content['attributes'], ['fixed' => 1, 'size' => 1]);
                            }
                            break;
                        case 'children':
                            $child['children'] = [];
                            break;
                    }
                }
            }

            if (!$isSection) {
                // Special handling for positions.
                if ($type === 'position') {
                    // TODO: we may want to simplify position id, but we need to take into account multiple instances of the same position key.
/*
                    if (!$subtype || $subtype === 'position') {
                        $id = 'position-' . (isset($child['attributes']['key']) ? $child['attributes']['key'] : rand(1000,9999));
                        unset ($child['attributes']['key']);
                    }
*/
                    unset ($child['attributes']['title']);
                }

                $value = $id;
                if (!empty($child['attributes']['enabled'])) {
                    unset ($child['attributes']['enabled']);
                }
            } else {
                // Recursively handle structure.
                $value = $this->build($child);
            }

            // Clean up defaults.
            if (empty($child['title']) || $child['title'] === 'Untitled' || $child['title'] === $this->getTitle($type, $subtype, $id)) {
                unset ($child['title']);
            }
            if (!$subtype || $subtype === $type) {
                unset ($child['subtype']);
            }

            // Remove id and children as we store data in flat structure with id being the key.
            unset ($child['id'], $child['children']);

            if ($type === 'offcanvas' && isset($child['attributes']['name'])) {
                unset ($child['attributes']['name']);
            }

            if ($ctype === 'block') {
                // Embed size into array key/value.
                if (isset($content['attributes']['size']) && $content['attributes']['size'] != 100) {
                    $size = $content['attributes']['size'];
                }
                unset ($content['attributes']['size']);
                // Embed parent block.
                if (!empty($content['attributes'])) {
                    $child['block'] = $content['attributes'];
                    unset ($content['attributes']);
                }
            }

            if (isset($child['attributes']['size'])) {
                if ($child['attributes']['size'] != 100 && is_string($value)) {
                    $size = $child['attributes']['size'];
                }
                unset ($child['attributes']['size']);
            }

            // Remove attributes if there aren't any.
            if (empty($child['attributes'])) {
                unset ($child['attributes']);
            }

            // Special handling for grid and block elements.
            if (in_array($type, ['grid', 'block']) && count($child) === 1 && isset($child['type'])) {
                $id = null;
            }

            // Check if type and subtype can be generated from the id.
            if ($subtype && (preg_match("/^{$type}-{$subtype}(-|$)/", $id))
                || (in_array($type, ['section', 'particle']) && preg_match("/^{$subtype}(-|$)/", $id))) {
                unset ($child['type'], $child['subtype']);
            } elseif (preg_match("/^{$type}(-|$)/", $id)) {
                unset ($child['type']);
            }

            // Add item configuration if not empty.
            if ($id && !empty($child)) {
                if (!is_string($value)) {
                    $this->structure[$id] = $child;
                } else {
                    $this->content[$id] = $child;
                }
            }

            // Add item to the layout.
            if (!is_string($value)) {
                // Add structural item.
                if ($id) {
                    // Sections and other complex items.
                    $id = isset($child['attributes']['boxed']) ? "/{$id}/" : $id;
                    $result[trim("{$id} {$size}")] = $value;
                } elseif (!empty($value)) {
                    // Simple grid / block item.
                    $result[] = $value;
                }
            } else {
                // Add content item.
                $result[] = trim("{$value} {$size}");
            }
        }

        // TODO: maybe collapse grid as well?
        if ($ctype && in_array($ctype, ['block']) && count($result) <= 1 && key($result) === 0) {
            unset ($this->structure[$content['id']]);
            return reset($result) ?: null;
        }

        return $result;
    }

    /**
     * @param string $string
     * @return array
     */
    protected function parseSectionString($string)
    {
        // Extract: "[section-id] [size]".
        $list = explode(' ', $string, 2);
        $section_id = array_shift($list);
        $size = ((float) array_shift($list)) ?: null;

        // Extract slashes from "/[section-id]/".
        $boxedLeft = $section_id[0] === '/';
        $boxedRight = $section_id[strlen($section_id)-1] === '/';
        $boxed = ($boxedLeft && $boxedRight ? '' : ($boxedLeft ? '1' : ($boxedRight ? '0' : null)));
        $section_id = trim($section_id, '/');

        // Extract section id if it exists: "[section]-[id]".
        $list = explode('-', $section_id, 2);

        // Get section and its type.
        $section = reset($list);
        $type = (in_array($section, $this->sections)) ? $section : 'section';
        $subtype = ($type !== 'section' || in_array($section, $this->structures)) ? $section : 'section';

        // Extract id.
        if ($type == 'section' && in_array($section, $this->structures)) {
            $id = array_pop($list);
        } else {
            $id = $section_id;
        }

        return [$type, $subtype, $id, $size, $section_id, $boxed];
    }

    /**
     * @param string $string
     * @return array
     */
    protected function parseContentString($string)
    {
        // Extract: "[type-subtype] [size]".
        $list = explode(' ', $string, 2);
        $content_id = array_shift($list);
        $size = ((float) array_shift($list)) ?: null;

        // Extract sub-type if it exists: "[type]-[subtype]-[id]".
        $list = explode('-', $content_id);

        // Get type, subtype and id.
        $type = reset($list);
        $test = end($list);
        $id = ((string)(int) $test === (string) $test) ? array_pop($list) : null;
        if (in_array($type, ['system', 'position', 'particle', 'spacer'])) {
            array_shift($list);
        } else {
            $type = 'particle';
        }
        $subtype = implode('-', $list);

        if ($type === 'position' && !in_array($subtype, ['module', 'widget'])) {
            $id = ($subtype ?: $type) . ($id !== null ? "-{$id}" : '');
            $subtype = 'position';
        }

        return [$type, $subtype ?: $type, $id, $size, $content_id];
    }

    /**
     * @param string $type
     * @param string $subtype
     * @param string $id
     * @return string
     */
    protected function getTitle($type, $subtype, $id)
    {
        if (in_array($type, $this->sections)) {
            if ($type === 'offcanvas') {
                return 'Offcanvas';
            }

            if ($type === 'grid' || $type === 'block') {
                return null;
            }

            return ucfirst((string)(int) $id === (string) $id ? ($subtype ?: $type) . "-{$id}" : $id);
        }

        if ($type === 'position' && !in_array($subtype, ['module', 'widget'])) {
            return ucfirst(preg_replace('/^position-(.*?[a-z])/ui', '\1', $id));
        }

        if ($type === 'system') {
            if ($subtype === 'messages') {
                return 'System Messages';
            }
            if ($subtype === 'content') {
                return 'Page Content';
            }
        }

        return ucfirst($subtype ?: $type);
    }

    /**
     * @param string $type
     * @param string $subtype
     * @param string $id
     * @return string
     */
    protected function id($type, $subtype = null, $id = null)
    {
        $result = [];
        if ($type !== 'particle') {
            $result[] = $type;
        }
        if ($subtype && $subtype !== $type) {
            $result[] = $subtype;
        }
        $key = implode('-', $result);

        if (!$id || isset($this->keys[$key][$id])) {
            while ($id = rand(1000, 9999)) {
                if (!isset($this->keys[$key][$id])) {
                    break;
                }
            }
        }

        $this->keys[$key][$id] = true;

        return $key . '-'. $id;
    }
}
