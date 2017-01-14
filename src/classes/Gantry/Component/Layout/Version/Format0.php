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
 * Read layout from Layout Manager yaml file.
 */
class Format0 extends Format1
{
    /**
     * @return array
     */
    public function load()
    {
        $data = &$this->data;

        $preset = isset($data['preset']) && is_array($data['preset']) ? $data['preset'] : [];

        $result = is_array($data['children']) ? $this->object($data['children']) : [];

        $invisible = [
            'offcanvas' => $this->parse('offcanvas', [], 0),
            'atoms' => $this->parse('atoms', [], 0)
        ];
        foreach ($result as $key => &$item) {
            if (isset($invisible[$item->type])) {
                $invisible[$item->type] = $item;
                unset($result[$key]);
            }
        }

        $result += $invisible;

        $result = array_values($result);

        return ['preset' => $preset] + $result;
    }

    protected function object(array $items, $container = true)
    {
        foreach ($items as &$item) {
            $item = (object) $item;

            if (isset($item->attributes) && (is_array($item->attributes) || is_object($item->attributes))) {
                $item->attributes = (object) $item->attributes;
            } else {
                $item->attributes = (object) [];
            }

            if (!empty($item->children) && is_array($item->children)) {
                $item->children = $this->object($item->children, false);
            }

            $this->normalize($item, $container);
        }

        return $items;
    }
}
