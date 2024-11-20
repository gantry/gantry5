<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Content;

use Gantry\Joomla\Category\Category;
use Gantry\Joomla\Category\CategoryFinder;
use Gantry\Joomla\Object\Collection;
use Gantry\Joomla\Object\Finder;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

/**
 * Class ContentFinder
 * @package Gantry\Joomla\Content
 */
class ContentFinder extends Finder
{
    /** @var string */
    protected $table = '#__content';
    /** @var bool */
    protected $readonly = true;
    /** @var array */
    protected $state = [];

    /**
     * Makes all created objects as readonly.
     *
     * @param bool $readonly
     * @return $this
     */
    public function readonly($readonly = true)
    {
        $this->readonly = (bool)$readonly;

        return $this;
    }

    /**
     * @param bool $object
     * @return Collection|string[]
     */
    public function find($object = true)
    {
        $ids = parent::find();

        if (!$object) {
            return $ids;
        }

        return Content::getInstances($ids, $this->readonly);
    }

    /**
     * @param int|int[] $ids
     * @param bool $include
     * @return $this
     */
    public function id($ids, $include = true)
    {
        return $this->addToGroup('a.id', $ids, $include);
    }

    /**
     * @param int|int[] $ids
     * @param bool $include
     * @return $this
     */
    public function author($ids, $include = true)
    {
        return $this->addToGroup('a.created_by', $ids, $include);
    }

    /**
     * @param int|int[] $ids
     * @param bool $include
     * @return $this
     */
    public function category($ids, $include = true)
    {
        if ($ids instanceof Collection) {
            $ids = $ids->toArray();
        } else {
            $ids = (array)$ids;
        }

        array_walk($ids, static function (&$item) { $item = $item instanceof Category ? $item->id : (int) $item; });

        return $this->addToGroup('a.catid', $ids, $include);
    }

    /**
     * @param int|bool $featured
     * @return $this
     */
    public function featured($featured = true)
    {
        $featured = (int)((bool)$featured);
        $this->where('a.featured', '=', $featured);

        return $this;
    }

    /**
     * @param string|int|bool $language
     * @return $this
     */
    public function language($language = true)
    {
        if (!$language) {
            return $this;
        }
        if ($language === true || is_numeric($language)) {
            /** @var CMSApplication $application */
            $application = Factory::getApplication();
            $language = $application->getLanguage()->getTag();
        }
        return $this->where('a.language', 'IN', [$language, '*']);
    }

    /**
     * @param int|int[] $published
     * @return $this
     */
    public function published($published = 1)
    {
        if (!\is_array($published)) {
            $published = [(int)$published];
        }
        return $this->where('a.state', 'IN', $published);
    }

    /**
     * @param bool $authorised
     * @return $this
     */
    public function authorised($authorised = true)
    {
        if (!$authorised) {
            return $this;
        }

        $unpublished = CategoryFinder::getUnpublished('content');

        if ($unpublished) {
            $this->where('a.catid', 'NOT IN', $unpublished);
        }

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $user = $application->getIdentity();

        if (!$user) {
            $this->skip = true;

            return $this;
        }

        // Filter by start and end dates.
        if (!$user->authorise('core.edit.state', 'com_content') && !$user->authorise('core.edit', 'com_content')) {
            // Define null and now dates
            $nowDate = $this->query->quote(Factory::getDate()->toSql());

            $nullDateUp = $this->query->isNullDatetime('a.publish_up');
            $nullDateDown = $this->query->isNullDatetime('a.publish_down');

            $this->query
                ->where('(' . $nullDateUp . ' OR a.publish_up <= ' . $nowDate . ')')
                ->where('(' . $nullDateDown . ' OR a.publish_down >= ' . $nowDate . ')')
                ->where('a.state >= 1')
            ;
        }

        $groups = $user->getAuthorisedViewLevels();

        $this->query->join('INNER', '#__categories AS c ON c.id = a.catid');

        return $this->where('a.access', 'IN', $groups)->where('c.access', 'IN', $groups);
    }

    /**
     * @param string $key
     * @param int|int[] $ids
     * @param bool $include
     * @return $this
     */
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
