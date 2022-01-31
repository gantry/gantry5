<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Category;

use Gantry\Joomla\Object\Finder;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

/**
 * Class CategoryFinder
 * @package Gantry\Joomla\Category
 */
class CategoryFinder extends Finder
{
    /** @var string */
    protected $table = '#__categories';
    /** @var string */
    protected $extension = 'com_content';
    /** @var bool */
    protected $readonly = true;

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

        return Category::getInstances($ids, $this->readonly);
    }

    /**
     * @param int|int[] $ids
     * @param int $levels
     * @return $this
     */
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

    /**
     * @param string|bool|int $language
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
        if (!is_array($published)) {
            $published = (array) ((int)$published);
        }
        return $this->where('a.published', 'IN', $published);
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

        // Ignore unpublished categories.
        $unpublished = self::getUnpublished($this->extension);

        if ($unpublished) {
            $this->where('a.id', 'NOT IN', $unpublished);
        }

        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Check authorization.
        $user = $app->getIdentity();
        $groups = $user ? $user->getAuthorisedViewLevels() : [];
        if (!$groups) {
            $this->skip = true;

            return $this;
        }

        return $this->where('a.access', 'IN', $groups);
    }

    /**
     * @param string $extension
     * @return $this
     */
    public function extension($extension)
    {
        $this->extension = static::getExtension($extension);

        return $this->where('a.extension', '=', $this->extension);
    }

    /**
     * @param string $extension
     * @return string
     */
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

    /**
     * @param $extension
     * @return array
     */
    public static function getUnpublished($extension)
    {
        static $list;

        if ($list === null) {
            $db = Factory::getDbo();

            $query = $db->getQuery(true)
                ->select('cat.id AS id')
                ->from('#__categories AS cat')
                ->join('LEFT', '#__categories AS parent ON cat.lft BETWEEN parent.lft AND parent.rgt')
                ->where('parent.extension = ' . $db->quote(static::getExtension($extension)))
                ->where('parent.published != 1 AND cat.published < 1')
                ->group('cat.id');

            $db->setQuery($query);
            $list = $db->loadColumn() ?: [];
        }

        return $list;
    }
}
