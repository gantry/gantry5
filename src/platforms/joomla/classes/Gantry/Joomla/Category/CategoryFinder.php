<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Category;

use Gantry\Joomla\Object\Finder;

class CategoryFinder extends Finder
{
    protected $table = '#__categories';
    protected $extension = 'com_content';
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

        return Category::getInstances($ids, $this->readonly);
    }

    public function id($ids, $levels = 0)
    {
        if ($ids && $levels) {
            $ids = (array) $ids;

            $db = $this->db;
            array_walk($ids, function (&$item) use ($db) { $item = $db->quote($item); });
            $idList = implode(',', $ids);

            // Create a subquery for the subcategory list
            $subQuery = $this->db->getQuery(true)
                ->select('sub.id')
                ->from('#__categories AS sub')
                ->join('INNER', '#__categories AS this ON sub.lft > this.lft AND sub.rgt < this.rgt')
                ->where("this.id IN ({$idList})");

            if (is_numeric($levels)) {
                $subQuery->where('sub.level <= this.level + ' . (int) $levels);
            }

            // Add the subquery to the main query
            $this->query->where("(a.id IN ({$idList}) OR a.id IN ({$subQuery->__toString()}))");
        } else {
            $this->where('a.id', 'IN', $ids);
        }

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
        return $this->where('a.published', 'IN', $published);
    }

    public function authorised($authorised = true)
    {
        if (!$authorised) {
            return $this;
        }

        // Ignore unpublished categories.
        $unpublished = $this->getUnpublished($this->extension);

        if ($unpublished) {
            $this->where('a.id', 'NOT IN', $unpublished);
        }

        // Check authorization.
        $user = \JFactory::getUser();
        $groups = $user->getAuthorisedViewLevels();

        return $this->where('a.access', 'IN', $groups);
    }

    public function extension($extension)
    {
        $this->extension = static::getExtension($extension);

        return $this->where('a.extension', '=', $this->extension);
    }

    public static function getExtension($extension)
    {
        static $map = [
            'article' => 'com_content',
            'articles' => 'com_content',
            'content' => 'com_content',
        ];

        if (isset($map[$extension])) {
            $extension = $map[$extension];
        }

        return $extension;
    }

    public static function getUnpublished($extension)
    {
        static $list;

        if ($list === null) {
            $db = \JFactory::getDbo();

            $query = $db->getQuery(true)
                ->select('cat.id AS id')
                ->from('#__categories AS cat')
                ->join('LEFT', '#__categories AS parent ON cat.lft BETWEEN parent.lft AND parent.rgt')
                ->where('parent.extension = ' . $db->quote(static::getExtension($extension)))
                ->where('parent.published != 1 AND cat.published < 1')
                ->group('cat.id');

            $db->setQuery($query);
            $list = $db->loadColumn();
        }

        return $list;
    }
}
