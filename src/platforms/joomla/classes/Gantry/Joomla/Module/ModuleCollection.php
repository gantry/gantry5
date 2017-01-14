<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Module;

use Gantry\Joomla\Object\Collection;

class ModuleCollection extends Collection
{
    public function toArray()
    {
        return $this->__call('toArray', []);
    }

    public function export()
    {
        $assignments = $this->assignments();
        $paths = $this->getAssignmentPath($this->values($assignments));

        $items = $this->toArray();
        $positions = [];

        // Convert assignments to our format.
        foreach ($items as $item) {
            $position = $item['position'];
            $name = $item['options']['type'] . '-' . $item['id'];

            if ($position === '') {
                continue;
            }
            if (empty($item['assignments'])) {
                $item['assignments'] = [];
            } elseif (in_array(0, $item['assignments'])) {
                $item['assignments'] = ['page' => true];
            } else {
                $list = [];
                foreach ($item['assignments'] as $assignment) {
                    $key = abs($assignment);
                    if (isset($paths[$key])) {
                        $list[$paths[$key]] = $assignment > 0 ? 1 : -1;
                    }
                }
                $item['assignments'] = ['page' => [$list]];
            }
            unset($item['position'], $item['id'], $item['ordering']);

            $positions[$position][$name] = $item;
        }

        return $positions;
    }

    public function assignments()
    {
        $this->loadAssignments();

        return $this->__call('assignments', []);
    }

    public function loadAssignments()
    {
        $ids = $this->defined('assignments', false);
        $ids = array_filter($ids);

        if (!$ids) {
            return;
        }

        $idlist = implode(',', array_keys($ids));

        $db = \JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('moduleid, menuid')->from('#__modules_menu')->where("moduleid IN ($idlist)");
        $db->setQuery($query);

        $assignments = $db->loadRowList();

        $list = [];
        foreach ($assignments as $value) {
            $list[$value[0]][] = (int) $value[1];
        }

        foreach ($this as $module) {
            $module->assignments(isset($list[$module->id]) ? $list[$module->id] : []);
        }
    }

    protected function getAssignmentPath(array $ids)
    {
        if (!$ids) {
            return [];
        }

        $idlist = implode(',', array_map('intval', $ids));

        $db = \JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('id, path')->from('#__menu')->where("id IN ($idlist)");
        $db->setQuery($query);

        $paths = $db->loadRowList();

        $list = [];
        foreach ($paths as $value) {
            $list[$value[0]] = $value[1];
        }

        return $list;
    }

    protected function values($values)
    {
        $list = [];
        foreach ($values as $array) {
            $list = array_merge($list, (array) $array);
        }

        return array_unique($list);
    }
}
