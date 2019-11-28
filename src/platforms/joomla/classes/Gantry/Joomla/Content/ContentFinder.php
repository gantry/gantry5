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
        $nullDate = $this->quote($this->db->getNullDate());
        $nowDate = $this->quote(\JFactory::getDate()->toSql());

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
    
    public function tags($tags = [], $matchAll = false)
    {
        $tagTitles = !empty($tags['title'][0]) ? $tags['title'][0] : NULL;
        $tagIds = !empty($tags['id'][0]) ? $tags['id'][0] : NULL;
        $condition = '';
        $result = $this;
        
        if (is_null($tagTitles) && is_null($tagIds)) {
            return $result;
        }

        // match all tag ids a/o titles
        if ($matchAll){
            
            //build up sub query and check for count
            if(!is_null($tagTitles)) {
                $condition .=  "({$this->tagsCountSubQuery($tagTitles)}) >= " . count($tagTitles);
            }
            
            if (!is_null($tagIds)) {
                if (strlen($condition) > 0){
                    $condition .= ' OR ';
                }
    
                $condition .=  "({$this->tagsCountSubQuery($tagIds, true)}) >= " . count($tagIds);
            }
            $result = $this->query->where($condition);

        // match any tag id a/o title
        } else {

            $this->query->join('INNER', '#__contentitem_tag_map AS tm ON tm.content_item_id = a.id');

            // check if tag title exists for article
            if (!is_null($tagTitles)) {
                $this->query->join('INNER', '#__tags AS ta ON tm.tag_id = ta.id');
                $condition .= "{$this->quoteName('ta.title')} IN {$this->toQuotedList($tagTitles)}";
            }

            // check if tag id exists for article
            if (!is_null($tagIds)) {
                if(strlen($condition) > 0){
                    $condition .= ' OR ';
                }

                array_map('intval', $tagIds);
                $condition .= "{$this->quoteName('tm.tag_id')} IN {$this->toQuotedList($tagIds)}";
            }

            $this->query->where($condition);
            $result = $this->where('tm.type_alias', '=', 'com_content.article');
        }
        
        return $result;
    }
    
    /**
     * Creates a sub query to retrieve the tag id or tag title count.
     *
     * @param  array         $tags        Tag ids or tag titles.
     * @param  boolean       $ids         Match ids or titles
     *
     * @return query
     */
    
    protected function tagsCountSubQuery($tags = [], $ids = false)
    {
        // build up sub query for tag count
        $subQuery = $this->db->getQuery(true)->select('COUNT(tm_s.tag_id)')->from($this->table . ' AS a_s');
        $subQuery->join('INNER', '#__contentitem_tag_map AS tm_s ON tm_s.content_item_id = a_s.id');
        
        if (!$ids) {
            $subQuery->join('INNER', '#__tags AS ta_s ON tm_s.tag_id = ta_s.id');
        }
        
        // the referenced article has to match
        $subQuery->where("{$this->quoteName('a_s.id')} = {$this->quoteName('a.id')}");
        
        // check for tag ids or tag titles
        if ($ids) {
            $subQuery->where("{$this->quoteName('tm_s.tag_id')} IN {$this->toQuotedList($tags)}");
        } else {
            $subQuery->where("{$this->quoteName('ta_s.title')} IN {$this->toQuotedList($tags)}");
        }
        
        return $subQuery->where("{$this->quoteName('tm_s.type_alias')} = {$this->quote('com_content.article')}");
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
