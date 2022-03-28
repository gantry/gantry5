<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\MenuItem;

use Gantry\Joomla\Object\Collection;
use Gantry\Joomla\Object\Finder;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

/**
 * Class MenuItemFinder
 * @package Gantry\Joomla\MenuItem
 */
class MenuItemFinder extends Finder
{
    /** @var string */
    protected $table = '#__menu';
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

        return MenuItem::getInstances($ids, $this->readonly);
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
        return $this->where('a.published', 'IN', $published);
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
