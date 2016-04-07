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

use Gantry\Framework\Base\Gantry;
use Gantry\Joomla\Category\Category;
use Gantry\Joomla\Object\Object;

class Content extends Object
{
    static protected $instances = [];

    static protected $table = 'Content';
    static protected $order = 'id';

    protected function initialize()
    {
        $this->images = json_decode($this->images);
        $this->urls = json_decode($this->urls);
        $this->attribs = json_decode($this->attribs);
        $this->metadata = json_decode($this->metadata);
    }

    public function author()
    {
        return \JUser::getInstance($this->created_by);
    }

    public function category()
    {
        return Category::getInstance($this->catid);
    }

    public function categories()
    {
        $category = $this->category();

        return array_merge($category->parents(), [$category]);
    }

    public function route()
    {
        require_once JPATH_SITE . '/components/com_content/helpers/route.php';

        $category = $this->category();

        return \JRoute::_(\ContentHelperRoute::getArticleRoute($this->id . '-' . $this->alias, $category->id . '-' . $category->alias), false);
    }

    public function render($file)
    {
        return Gantry::instance()['theme']->render($file, ['content' => $this]);
    }

    public function compile($string)
    {
        return Gantry::instance()['theme']->compile($string, ['content' => $this]);
    }
}
