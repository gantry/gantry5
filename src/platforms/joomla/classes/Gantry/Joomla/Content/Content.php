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

use Gantry\Framework\Gantry;
use Gantry\Joomla\Category\Category;
use Gantry\Joomla\Object\AbstractObject;

class Content extends AbstractObject
{
    static protected $instances = [];

    static protected $table = 'Content';
    static protected $order = 'id';

    public function initialize()
    {
        if (!parent::initialize()) {
            return false;
        }

        $this->images = json_decode($this->images);
        $this->urls = json_decode($this->urls);
        $this->attribs = json_decode($this->attribs);
        $this->metadata = json_decode($this->metadata);

        $nullDate = \JFactory::getDbo()->getNullDate();
        if ($this->modified === $nullDate) {
            $this->modified = $this->created;
        }
        if ($this->publish_up === $nullDate) {
            $this->publish_up = $this->created;
        }

        return true;
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

    public function text()
    {
        return $this->introtext . ' ' . $this->fulltext;
    }

    public function preparedText()
    {
        return \JHtml::_('content.prepare', $this->text());
    }

    public function preparedIntroText()
    {
        return \JHtml::_('content.prepare', $this->introtext);
    }

    public function readmore()
    {
        return (bool)strlen($this->fulltext);
    }

    public function route()
    {
        require_once JPATH_SITE . '/components/com_content/helpers/route.php';

        $category = $this->category();

        return \JRoute::_(\ContentHelperRoute::getArticleRoute($this->id . ':' . $this->alias, $category->id . ':' . $category->alias), false);
    }

    public function edit()
    {
        $user = \JFactory::getUser();
        $asset = "com_content.article.{$this->id}";

        if ($user->authorise('core.edit', $asset) || $user->authorise('core.edit.own', $asset)) {
            return "index.php?option=com_content&task=article.edit&a_id={$this->id}&tmpl=component";
        }

        return false;
    }

    public function render($file)
    {
        return Gantry::instance()['theme']->render($file, ['article' => $this]);
    }

    public function compile($string)
    {
        return Gantry::instance()['theme']->compile($string, ['article' => $this]);
    }

    public function toArray()
    {
        return $this->getProperties(true) + [
            'category' => [
                'alias' => $this->category()->alias,
                'title' => $this->category()->title
            ],
            'author' => [
                'username' => $this->author()->username,
                'fullname' => $this->author()->name
            ],
        ];
    }
}
