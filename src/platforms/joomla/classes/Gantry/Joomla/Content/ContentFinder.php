<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Content;

use Gantry\Joomla\Category\Category;
use Gantry\Joomla\Category\CategoryFinder;
use Gantry\Joomla\Object\Collection;
use Gantry\Joomla\Object\Finder;

class ContentFinder extends Finder
{
    protected $table = '#__content';
    protected $readonly = true;
    protected $state = [];

    /**
     * Makes all created objects as readonly.
     *
     * @return $this
     */
    public function readonly($readonly = true)
    {
        $this->readonly = (bool)$readonly;

        return $this;
    }

    public function find($object = true)
    {
        $ids = parent::find();

        if (!$object) {
            return $ids;
        }

        return Content::getInstances($ids, $this->readonly);
    }

    public function id($ids, $include = true)
    {
        return $this->addToGroup('a.id', $ids, $include);
    }

    public function author($ids, $include = true)
    {
        return $this->addToGroup('a.created_by', $ids, $include);
    }

    public function category($ids, $include = true)
    {
        if ($ids instanceof Collection) {
            $ids = $ids->toArray();
        } else {
            $ids = (array)$ids;
        }

        array_walk($ids, function (&$item) { $item = $item instanceof Category ? $item->id : (int) $item; });

        return $this->addToGroup('a.catid', $ids, $include);
    }

    public function featured($featured = true)
    {
        $featured = intval((bool)$featured);
        $this->where('a.featured', '=', $featured);

        return $this;
    }

    public function language($language = true)
    {
        if (!$language) {
            return $this;
        }
        if ($language === true || is_numeric($language)) {
            $language = \JFactory::getLanguage()->getTag();
        }
        return $this->where('a.language', 'IN', [$language, '*']);
    }

    public function published($published = 1)
    {
        if (!is_array($published)) {
            $published = (array) intval($published);
        }
        return $this->where('a.state', 'IN', $published);
    }

    public function authorised($authorised = true)
    {
        if (!$authorised) {
            return $this;
        }

        $unpublished = CategoryFinder::getUnpublished('content');
        if ($unpublished) {
            $this->where('a.catid', 'NOT IN', $unpublished);
        }

        $user = \JFactory::getUser();

        // Define null and now dates
        $nullDate = $this->db->quote($this->db->getNullDate());
        $nowDate = $this->db->quote(\JFactory::getDate()->toSql());

        // Filter by start and end dates.
        if (!$user->authorise('core.edit.state', 'com_content') && !$user->authorise('core.edit', 'com_content')) {
            $this->query
                ->where("(a.publish_up = {$nullDate} OR a.publish_up <= {$nowDate})")
                ->where("(a.publish_down = {$nullDate} OR a.publish_down >= {$nowDate})")
                ->where("a.state >= 1")
            ;
        }

        $groups = $user->getAuthorisedViewLevels();

        $this->query->join('INNER', '#__categories AS c ON c.id = a.catid');

        return $this->where('a.access', 'IN', $groups)->where('c.access', 'IN', $groups);
    }

    protected function addToGroup($key, $ids, $include = true)
    {
        $op = $include ? 'IN' : 'NOT IN';

        if (isset($this->state[$key][$op])) {
            $this->state[$key][$op] = array_merge($this->state[$key][$op], $ids);
        } else {
            $this->state[$key][$op] = $ids;
        }

        return $this;
    }

    protected function prepare()
    {
        foreach ($this->state as $key => $list) {
            foreach ($list as $op => $group) {
                $this->where($key, $op, array_unique($group));
            }
        }
    }
}
