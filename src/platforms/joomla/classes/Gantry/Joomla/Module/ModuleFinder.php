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

use Gantry\Joomla\Object\Finder;

class ModuleFinder extends Finder
{
    protected $table = '#__modules';
    protected $readonly = true;
    protected $state = [];
    protected $published = [0, 1];
    protected $limit = 0;

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

        return Module::getInstances($ids, $this->readonly);
    }

    public function id($ids, $include = true)
    {
        return $this->addToGroup('a.id', $ids, $include);
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

        $this->published = $published;

        return $this;
    }

    public function particle()
    {
        return $this->where('a.module', '=', 'mod_gantry5_particle');
    }

    public function authorised($authorised = true)
    {
        if (!$authorised) {
            return $this;
        }

        $groups = \JFactory::getUser()->getAuthorisedViewLevels();

        return $this->where('a.access', 'IN', $groups);
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
        $this->where('client_id', '=', 0)->where('published', 'IN', $this->published)->order('position')->order('ordering');
        foreach ($this->state as $key => $list) {
            foreach ($list as $op => $group) {
                $this->where($key, $op, array_unique($group));
            }
        }
    }
}
