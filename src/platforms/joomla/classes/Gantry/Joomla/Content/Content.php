<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Content;

use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Gantry\Joomla\Category\Category;
use Gantry\Joomla\Object\AbstractObject;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Content\Site\Model\ArticleModel;

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
    /** @var array */
    protected static $instances = [];
    /** @var string */
    protected static $table = 'Content';
    /** @var string */
    protected static $order = 'id';

    /**
     * @return bool
     */
    public function initialize()
    {
        if (!parent::initialize()) {
            return false;
        }

        $this->images   = json_decode($this->images, false);
        $this->urls     = json_decode($this->urls, false);
        $this->attribs  = json_decode($this->attribs, false);
        $this->metadata = json_decode($this->metadata, false);

        $nullDate = Factory::getDbo()->getNullDate();

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
        return Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->created_by);
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
        return (bool) \strlen($this->fulltext);
    }

    /**
     * @return string
     */
    public function route()
    {
        $category = $this->category();
        $url = RouteHelper::getArticleRoute($this->id . ':' . $this->alias, $category->id . ':' . $category->alias);

        return \htmlspecialchars_decode(Route::_($url, false), ENT_COMPAT);
    }

    /**
     * @return bool|string
     */
    public function edit()
    {
        $user  = Factory::getApplication()->getIdentity();
        $asset = "com_content.article.{$this->id}";

        if ($user && ($user->authorise('core.edit', $asset) || $user->authorise('core.edit.own', $asset))) {
            $contentUrl = RouteHelper::getArticleRoute($this->id . ':' . $this->alias, $this->catid);
            $url = $contentUrl . '&task=article.edit&a_id=' . $this->id;

            return \htmlspecialchars_decode(Route::_($url), ENT_COMPAT);
        }

        return false;
    }

    /**
     * @param string $file
     * @return string
     */
    public function render($file)
    {
        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme = $gantry['theme'];

        return $theme->render($file, ['article' => $this]);
    }

    /**
     * @param string $string
     * @return string
     */
    public function compile($string)
    {
        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme = $gantry['theme'];

        return $theme->compile($string, ['article' => $this]);
    }

    /**
     * @param $config
     * @return object
     */
    public function object($config = [])
    {
        $config += [
            'ignore_request' => true
        ];

        $app    = Factory::getApplication();
        $user   = $app->getIdentity();
        $params = $app->getParams();

        $model = new ArticleModel($config);
        $model->setState('article.id', $this->id);
        $model->setState('list.offset', 0);
        $model->setState('params', $params);

        // If $pk is set then authorise on complete asset, else on component only
        $asset = empty($this->id) ? 'com_content' : 'com_content.article.' . $this->id;

        if ((!$user->authorise('core.edit.state', $asset)) && (!$user->authorise('core.edit', $asset))) {
            $model->setState('filter.published', ContentComponent::CONDITION_PUBLISHED);
            $model->setState('filter.archived', ContentComponent::CONDITION_ARCHIVED);
        }

        $model->setState('filter.language', Multilanguage::isEnabled());

        return $model->getItem($this->id);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $properties = $this->getProperties(true) + [
            'category' => [
                'alias' => $this->category()->alias,
                'title' => $this->category()->title
            ],
            'author' => [
                'username' => $this->author()->username,
                'fullname' => $this->author()->name
            ],
        ];

        foreach ($properties as $key => $val) {
            if (str_starts_with($key, '_')) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * @return string
     */
    public function exportSql()
    {
        return $this->getCreateSql(['asset_id', 'created_by', 'modified_by', 'checked_out', 'checked_out_time', 'publish_up', 'publish_down', 'version', 'xreference']) . ';';
    }

    /**
     * @param mixed $table
     * @param mixed $k
     * @param mixed $v
     * @return string
     */
    protected function fixValue($table, $k, $v)
    {
        if ($k === '`created`' || $k === '`modified`') {
            $v = 'NOW()';
        } elseif (\is_string($v)) {
            $dbo = $table->getDbo();
            $v = $dbo->quote($v);
        }

        return $v;
    }
}
