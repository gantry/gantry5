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
    protected $sections = ['atoms', 'wrapper', 'container', 'section', 'grid', 'block', 'offcanvas'];
    protected $structures = ['div', 'section', 'aside', 'nav', 'article', 'header', 'footer', 'main'];

    protected $data;
    protected $atoms;
    protected $structure;
    protected $content;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function load()
    {
        $data = &$this->data;

        // Create atoms section to the layout if it doesn't exist.
        if (!isset($data['atoms'])) {
            $data['atoms'] = [];
        }
        $data['layout']['atoms'] =& $data['atoms'];

        // Add atom-prefix to all atoms.
        foreach ($data['atoms'] as &$atom) {
            if (strpos($atom, 'atom-') !== 0) {
                $atom = "atom-{$atom}";
            }
        }

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

    public function store(array $preset, array $structure)
    {
        $this->atoms = [];
        $this->structure = [];
        $this->content = [];

        $structure = ['children' => json_decode(json_encode($structure), true)];
        $structure = $this->build($structure);

        $result = [
            'version' => 2,
            'preset' => $preset,
            'layout' => $structure,
            'atoms' => $this->atoms,
            'structure' => $this->structure,
            'content' => $this->content
        ];

        return $result;
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
            $result = (object) ['id' => $this->id($this->scopes[$scope]), 'type' => $this->scopes[$scope], 'subtype' => false, 'layout' => true, 'attributes' => (object) []];
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
            $type = (in_array($section, $this->sections)) ? $section : 'section';
            $subtype = ($type !== 'section' || in_array($section, $this->sections)) ? $section : 'section';

            if ($type == 'grid') {
                $scope = 1;
            }
            if ($type == 'block') {
                $scope = 0;
            }
            // Extract id.
            if ($type == 'section' && in_array($section, $this->structures)) {
                $id = array_pop($list);
            } else {
                $id = $section_id;
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
            $result = (object) $result;
            $result->attributes = (object) $result->attributes;

            if ($size) {
                $result->size = $size;
            }
        }

        if (!empty($content)) {
            $result->children = [];
            foreach ($content as $child => &$params) {
                if (!$params && !is_array($params)) {
                    $params = [];
                }
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

    protected function getTitle($type, $subtype, $id)
    {
        if (in_array($type, $this->sections)) {
            if ($type === 'offcanvas') {
                return 'Offcanvas';
            }

            return ucfirst($id);
        }

        if ($type === 'position') {
            return ucfirst($id);
        }

        if ($type === 'system') {
            if ($subtype === 'messages') {
                return 'System Messages';
            }
            if ($subtype === 'content') {
                return 'Page Content';
            }
        }

        // TODO: remove
        if ($type === 'pagecontent') {
            if ($subtype == 'system-messages') {
                return 'System Messages';
            } else {
                return 'Page Content';
            }
        }

        return ucfirst($subtype ?: $type);
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

        // Extract sub-type if it exists: "[type]-[subtype]-[id]".
        $list = explode('-', $type_subtype);

        // Get type, subtype and id.
        $type = reset($list);
        $test = end($list);
        $id = ((string)(int) $test === (string) $test) ? array_pop($list) : null;
        if (in_array($type, ['system', 'position', 'particle', 'atom'])) {
            array_shift($list);
        } else {
            $type = 'particle';
        }
        $subtype = implode('-', $list);

        if ($type === 'system') {
            if ($subtype === 'messages') {
                $type = 'pagecontent';
                $subtype = 'system-messages';
            }
            if ($subtype === 'content') {
                $type = 'pagecontent';
                $subtype = 'pagecontent';
            }
        }

        if ($type === 'position') {
            $key = $type . ($subtype ? "-{$subtype}" : '') . ($id !== null ? "-{$id}" : '');
            $subtype = false;
            $id = $key;
        }

        $title = $this->getTitle($type, $subtype, $id);

        $result = isset($this->data['content'][$type_subtype]) ? (array) $this->data['content'][$type_subtype] : [];
        $result += ['id' => $this->id($type, $subtype, $id), 'title' => $title, 'type' => $type, 'subtype' => $subtype, 'attributes' => []];

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
            $result = (object) ['id' => $this->id('block'), 'type' => 'block', 'subtype' => false, 'layout' => true, 'children' => [$result], 'attributes' => new \stdClass];
            if ($size) {
                $result->attributes->size = $size;
            }
        }
        if ($scope == 0) {
            $result = (object) ['id' => $this->id('grid'), 'type' => 'grid', 'subtype' => false, 'layout' => true, 'children' => [$result], 'attributes' => new \stdClass];
        }

        return $result;
    }

    protected function build(&$content)
    {
        $result = [];
        $ctype = isset($content['type']) ? $content['type'] : null;

        // Clean up all items for saving.
        foreach ($content['children'] as $child) {
            $value = null;
            $size = null;
            $id = $child['id'];
            $type = $child['type'];
            $subtype = $child['subtype'];

            if ($type === 'atom') {
                // Handle atoms.
                $this->atoms[] = $id;
            } elseif (!in_array($type, $this->sections)) {
                // Special handling for pagecontent.
               if ($type === 'pagecontent') {
                   $child['type'] = $type = 'system';
                   $child['subtype'] = $subtype = ($subtype === 'pagecontent') ? 'content' : 'messages';
                }

                // Special handling for positions.
                if ($type === 'position') {
                    $id = $child['attributes']['key'];
                    if (strpos($id, 'position-') !== 0) {
                        $id = 'position-' . $id;
                    }
                    unset ($child['attributes']['title'], $child['attributes']['key']);
                }

                $value = $id;
                if (!empty($child['attributes']['enabled'])) {
                    unset ($child['attributes']['enabled']);
                }
            } else {
                // Recursively handle structure.
                $value = $this->build($child);

                if ($id === 'atoms') {
                    continue;
                }
            }

            // Clean up defaults.
            if (!$child['title'] || $child['title'] === 'Untitled' || $child['title'] === $this->getTitle($type, $subtype, $id)) {
                unset ($child['title']);
            }
            if (!$subtype) {
                unset ($child['subtype']);
            }

            // Remove id and children as we store data in flat structure with id being the key.
            unset ($child['id'], $child['children']);

            if ($type === 'offcanvas' && isset($child['attributes']['name'])) {
                unset ($child['attributes']['name']);
            }

            // Embed size into array key/value.
            if (!is_string($value) && $ctype === 'block' && isset($content['attributes']['size']) && $content['attributes']['size'] != 100) {
                $size = $content['attributes']['size'];
            }
            if (isset($child['attributes']['size'])) {
                if ($child['attributes']['size'] != 100 && is_string($value)) {
                    $value .= ' ' . $child['attributes']['size'];
                }
                unset ($child['attributes']['size']);
            }

            // Remove attributes if there aren't any.
            if (!$child['attributes']) {
                unset ($child['attributes']);
            }

            // Special handling for grid and block elements.
            if (in_array($type, ['grid', 'block']) && count($child) === 1) {
                $id = null;
            }

            // Add item to the layout (skip atoms).
            if ($value) {
                if ($id && !is_string($value)) {
                    // Add structural item.
                    $result[trim("{$id} {$size}")] = $value;
                } else {
                    // Add content or simple grid / block item.
                    $result[] = $value;
                }
            }

            // Add item configuration if not empty.
            $count = count($child) - intval(isset($child['type'])) - intval(isset($child['subtype']));
            if ($id && !is_string($value) && $count && $type !== 'atom') {
                $this->structure[$id] = $child;
            } elseif (($subtype && $id !== $subtype) || $count) {
                $this->content[$id] = $child;
            }
        }

        if ($ctype && in_array($ctype, ['grid', 'block']) && count($result) <= 1 && key($result) === 0) {
            unset ($this->structure[$content['id']]);
            return reset($result) ?: null;
        }
        return $result;
    }

    protected function id($type, $subtype = null, $id = null)
    {
        static $keys = [];

        // Special handling for pagecontent.
       if ($type === 'pagecontent') {
           $type = 'system';
           $subtype = $subtype === 'system-messages' ? 'messages' : 'content';
        }

        $result = [];
        if ($type !== 'particle' && $type !== 'atom') {
            $result[] = $type;
        }
        if ($subtype) {
            $result[] = $subtype;
        }
        $key = implode('-', $result);
        if ($id !== null) {
            return $key . '-'. $id;
        }

        if (!isset($keys[$key])) {
            $keys[$key] = 0;
        }

        return $key . '-'. ++$keys[$key];
    }
}
