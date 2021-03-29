<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Module;

use Gantry\Joomla\Object\Finder;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

/**
 * Class ModuleFinder
 * @package Gantry\Joomla\Module
 */
class ModuleFinder extends Finder
{
    /** @var string */
    protected $table = '#__modules';
    /** @var bool */
    protected $readonly = true;
    /** @var array */
    protected $state = [];
    /** @var array */
    protected $published = [0, 1];
    /** @var int */
    protected $limit = 0;

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
     * @return array|\Gantry\Joomla\Object\Collection
     */
    public function find($object = true)
    {
        $ids = parent::find();

        if (!$object) {
            return $ids;
        }

        return Module::getInstances($ids, $this->readonly);
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

        $this->published = $published;

        return $this;
    }

    /**
     * @return ModuleFinder
     */
    public function particle()
    {
        return $this->where('a.module', '=', 'mod_gantry5_particle');
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

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $user = $application->getIdentity();

        $groups = $user ? $user->getAuthorisedViewLevels() : [];
        if (!$groups) {
            $this->skip = true;

            return $this;
        }

        return $this->where('a.access', 'IN', $groups);
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
        $this->where('client_id', '=', 0)->where('published', 'IN', $this->published)->order('position')->order('ordering');
        foreach ($this->state as $key => $list) {
            foreach ($list as $op => $group) {
                $this->where($key, $op, array_unique($group));
            }
        }
    }
}
