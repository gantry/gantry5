<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Category;

use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Gantry\Joomla\Object\AbstractObject;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;

/**
 * Class Category
 * @package Gantry\Joomla\Category
 *
 * @property $extension
 * @property $parent_id
 * @property $path
 * @property $alias
 * @property $params
 * @property $metadata
 */
class Category extends AbstractObject
{
    /** @var array */
    static protected $instances = [];
    /** @var string */
    static protected $table = 'Category';
    /** @var string */
    static protected $order = 'lft';

    /**
     * @return bool
     */
    public function initialize()
    {
        if (!parent::initialize()) {
            return false;
        }

        $this->params = json_decode($this->params, false);
        $this->metadata = json_decode($this->metadata, false);

        return true;
    }

    /**
     * @return Object|null
     */
    public function parent()
    {
        if ($this->alias !== $this->path)
        {
            $parent = Category::getInstance($this->parent_id);
        }

        return isset($parent) && $parent->extension === $this->extension ? $parent : null;
    }

    /**
     * @return array
     */
    public function parents()
    {
        $parent = $this->parent();

        return $parent ? array_merge($parent->parents(), [$parent]) : [];
    }

    /**
     * @return string
     */
    public function route()
    {
        if (version_compare(JVERSION, '4.0', '<')) {
            require_once JPATH_SITE . '/components/com_content/helpers/route.php';

            return Route::_(\ContentHelperRoute::getCategoryRoute($this->id . ':' . $this->alias), false);
        }

        require_once JPATH_SITE . '/components/com_content/src/Helper/RouteHelper.php';

        return Route::_(RouteHelper::getCategoryRoute($this->id . ':' . $this->alias), false);
    }

    /**
     * @param string $file
     * @return mixed
     */
    public function render($file)
    {
        /** @var Theme $theme */
        $theme = Gantry::instance()['theme'];

        return $theme->render($file, ['category' => $this]);
    }

    /**
     * @param string $string
     * @return mixed
     */
    public function compile($string)
    {
        /** @var Theme $theme */
        $theme = Gantry::instance()['theme'];

        return $theme->compile($string, ['category' => $this]);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $properties = $this->getProperties(true);

        foreach ($properties as $key => $val) {
            if (str_starts_with($key, '_')) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    public function exportSql()
    {
        return $this->getCreateSql(['asset_id', 'checked_out', 'checked_out_time', 'created_user_id', 'modified_user_id', 'hits', 'version']) . ';';
    }

    protected function fixValue($table, $k, $v)
    {
        if ($k === '`created_time`' || $k === '`modified_time`') {
            $v = 'NOW()';
        } elseif (is_string($v)) {
            $dbo = $table->getDbo();
            $v = $dbo->quote($v);
        }

        return $v;
    }
}
