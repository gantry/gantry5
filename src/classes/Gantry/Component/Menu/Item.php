<?php
namespace Gantry\Component\Menu;

use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;

class Item implements \ArrayAccess
{
    use ArrayAccessWithGetters;

    protected $items;

    public function __construct($name, array $item = [])
    {
        $parent = dirname($name);
        $alias = basename($name);

        $this->items = $item + [
            'id' => preg_replace('|[^a-z0-9]|i', '-', $name),
            'type' => 'link',
            'path' => $name,
            'alias' => $alias,
            'title' => ucfirst($alias),
            'link' => $name,
            'parent' => $parent != '.' ? $parent : '',
            'children' => [],
            'groups' => [],
            'layout' => 'default',
            'browserNav' => 0,
            'menu_text' => true,
            'visible' => true,
        ];
    }

    public function addChild(Item $child)
    {
        $this->items['children'][$child->alias] = $child;

        return $this;
    }

    public function removeChild(Item $child)
    {
        unset($this->items['children'][$child->alias]);

        return $this;
    }

    public function sortChildren($ordering)
    {
        // Array with keys that point to the items.
        $children =& $this->items['children'];

        if ($children) {
            if (is_array($ordering)) {
                // Remove extra items from ordering and reorder.
                $children = array_replace(array_intersect_key($ordering, $children), $children);
            } else {
                switch ((string) $ordering) {
                    case 'abc':
                        // Alphabetical ordering.
                        ksort($children);
                        break;
                    case 'cba':
                        // Reversed alphabetical ordering.
                        krsort($children);
                        break;
                }
            }
        }

        return $this;
    }


    public function reverse()
    {
        array_reverse($this->items['children'], true);
        array_reverse($this->items['groups'], true);

        return $this;
    }

    public function groupChildren(array $groups)
    {
        // Array with keys that point to the items.
        $children =& $this->items['children'];

        if ($children) {
            $ordered = [];
            foreach ($groups as $ordering) {
                if (!$ordering || !is_array($ordering)) {
                    continue;
                }

                // Get the items for this group with proper ordering.
                $group = array_replace(
                    array_intersect_key($ordering, $children), array_intersect_key($children, $ordering)
                );

                // Update remaining children.
                $children = array_diff_key($children, $ordering);

                // Build child ordering.
                $ordered += $group;

                // Add items to the current group.
                $this->items['groups'][] = $group;
            }

            if ($children) {
                // Add leftover children to the ordered list and to the first group.
                $ordered += $children;
                $this->items['groups'][0] += $children;
            }

            // Reorder children by their groups.
            $children = $ordered;
        }

        return $this;
    }
}
