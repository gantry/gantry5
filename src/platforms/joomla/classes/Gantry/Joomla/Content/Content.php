<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Content;

use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Gantry\Joomla\Category\Category;
use Gantry\Joomla\JoomlaFactory;
use Gantry\Joomla\Object\AbstractObject;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;

/**
 * Class Content
 * @package Gantry\Joomla\Content
 *
 * @property $images
 * @property $urls
 * @property $attribs
 * @property $metadata
 * @property $modified
 * @property $created
 * @property $publish_up
 * @property $created_by
 * @property $catid
 * @property $introtext
 * @property $fulltext
 * @property $alias
 */
class Content extends AbstractObject
{
    static protected $instances = [];

    static protected $table = 'Content';
    static protected $order = 'id';

    /**
     * @return bool
     */
    public function initialize()
    {
        if (!parent::initialize()) {
            return false;
        }

        $this->images = json_decode($this->images, false);
        $this->urls = json_decode($this->urls, false);
        $this->attribs = json_decode($this->attribs, false);
        $this->metadata = json_decode($this->metadata, false);

        $nullDate = JoomlaFactory::getDbo()->getNullDate();
        if ($this->modified === $nullDate) {
            $this->modified = $this->created;
        }
        if ($this->publish_up === $nullDate) {
            $this->publish_up = $this->created;
        }

        return true;
    }

    /**
     * @return User
     */
    public function author()
    {
        return User::getInstance($this->created_by);
    }

    /**
     * @return Object
     */
    public function category()
    {
        return Category::getInstance($this->catid);
    }

    /**
     * @return array
     */
    public function categories()
    {
        $category = $this->category();

        return array_merge($category->parents(), [$category]);
    }

    /**
     * @return string
     */
    public function text()
    {
        return $this->introtext . ' ' . $this->fulltext;
    }

    /**
     * @return string
     */
    public function preparedText()
    {
        return HTMLHelper::_('content.prepare', $this->text());
    }

    /**
     * @return string
     */
    public function preparedIntroText()
    {
        return HTMLHelper::_('content.prepare', $this->introtext);
    }

    /**
     * @return bool
     */
    public function readmore()
    {
        return (bool)\strlen($this->fulltext);
    }

    /**
     * @return string
     */
    public function route()
    {
        require_once JPATH_SITE . '/components/com_content/helpers/route.php';

        $category = $this->category();

        return Route::_(\ContentHelperRoute::getArticleRoute($this->id . ':' . $this->alias, $category->id . ':' . $category->alias), false);
    }

    /**
     * @return bool|string
     */
    public function edit()
    {
        $user = JoomlaFactory::getUser();
        $asset = "com_content.article.{$this->id}";

        if ($user->authorise('core.edit', $asset) || $user->authorise('core.edit.own', $asset)) {
            return "index.php?option=com_content&task=article.edit&a_id={$this->id}&tmpl=component";
        }

        return false;
    }

    /**
     * @param string $file
     * @return string
     */
    public function render($file)
    {
        /** @var Theme $theme */
        $theme = Gantry::instance()['theme'];

        return $theme->render($file, ['article' => $this]);
    }

    /**
     * @param string $string
     * @return string
     */
    public function compile($string)
    {
        /** @var Theme $theme */
        $theme = Gantry::instance()['theme'];

        return $theme->compile($string, ['article' => $this]);
    }

    /**
     * @return array
     */
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
