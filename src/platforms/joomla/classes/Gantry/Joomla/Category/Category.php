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

use Gantry\Framework\Gantry;
use Gantry\Joomla\Object\AbstractObject;

class Category extends AbstractObject
{
    static protected $instances = [];

    static protected $table = 'Category';
    static protected $order = 'lft';

    public function initialize()
    {
        if (!parent::initialize()) {
            return false;
        }

        $this->params = json_decode($this->params);
        $this->metadata = json_decode($this->metadata);

        return true;
    }

    public function parent()
    {
        if ($this->alias != $this->path)
        {
            $parent = Category::getInstance($this->parent_id);
        }

        return isset($parent) && $parent->extension == $this->extension ? $parent : null;
    }

    public function parents()
    {
        $parent = $this->parent();

        return $parent ? array_merge($parent->parents(), [$parent]) : [];
    }

    public function route()
    {
        require_once JPATH_SITE . '/components/com_content/helpers/route.php';

        return \JRoute::_(\ContentHelperRoute::getCategoryRoute($this->id . ':' . $this->alias), false);
    }

    public function render($file)
    {
        return Gantry::instance()['theme']->render($file, ['category' => $this]);
    }

    public function compile($string)
    {
        return Gantry::instance()['theme']->compile($string, ['category' => $this]);
    }

    public function toArray()
    {
        return $this->getProperties(true);
    }
}
