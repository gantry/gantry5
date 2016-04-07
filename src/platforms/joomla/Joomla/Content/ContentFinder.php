<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
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
        return $this->where('a.id', $include ? 'IN' : 'NOT IN', $ids);
    }

    public function featured($featured = true)
    {
        $featured = intval((bool)$featured);
        $this->where('a.featured', '=', $featured);

        return $this;
    }

    public function author($ids, $include = true)
    {
        return $this->where('a.created_by', $include ? 'IN' : 'NOT IN', $ids);
    }

    public function language($language = true)
    {
        if (!$language) {
            return $this;
        }
        if (is_numeric($language)) {
            $language = \JFactory::getLanguage()->getTag();
        }
        return $this->where('a.language', 'IN', [$language, '*']);
    }

    public function category($ids, $include = true)
    {
        if ($ids instanceof Collection) {
            $ids = $ids->toArray();
        } else {
            $ids = (array)$ids;
        }

        array_walk($ids, function (&$item) { $item = $item instanceof Category ? $item->id : (int) $item; });

        return $this->where('a.catid', $include ? 'IN' : 'NOT IN', $ids);
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
            $this->query->where("(a.publish_up = {$nullDate} OR a.publish_up <= {$nowDate})")
                ->where("(a.publish_down = {$nullDate} OR a.publish_down >= {$nowDate})");
        }

        $groups = $user->getAuthorisedViewLevels();

        $this->query->join('INNER', '#__categories AS c ON c.id = a.catid');

        return $this->where('a.access', 'IN', $groups)->where('c.access', 'IN', $groups);
    }

    /**
     * Filter by time, either on first or last post.
     *
     * @param \JDate $starting  Starting date or null if older than ending date.
     * @param \JDate $ending    Ending date or null if newer than starting date.
     *
     * @return $this
     */
    public function date(\JDate $starting = null, \JDate $ending = null)
    {
        // TODO
    }
}
