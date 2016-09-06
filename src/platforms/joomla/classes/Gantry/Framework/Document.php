<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
{
    protected static $availableFrameworks = [
        'jquery' => 'registerJquery',
        'jquery.framework' => 'registerJquery',
        'jquery.ui.core' => 'registerJqueryUiCore',
        'jquery.ui.sortable' => 'registerJqueryUiSortable',
        'bootstrap.2' => 'registerBootstrap2',
        'mootools' => 'registerMootools',
        'mootools.framework' => 'registerMootools',
        'mootools.core' => 'registerMootools',
        'mootools.more' => 'registerMootoolsMore'
    ];

    public static function registerAssets()
    {
        static::registerFrameworks();
        static::registerStyles();
        static::registerScripts();
    }

    public static function rootUri()
    {
        return rtrim(\JUri::root(true), '/') ?: '/';
    }

    protected static function registerStyles()
    {
        if (empty(self::$styles['head'])) {
            return;
        }

        krsort(self::$styles['head'], SORT_NUMERIC);

        $doc = \JFactory::getDocument();

        foreach (self::$styles['head'] as $styles) {
            foreach ($styles as $style) {
                switch ($style[':type']) {
                    case 'file':
                        $doc->AddStyleSheet($style['href'], $style['type'], $style['media'], $style['element']);
                        break;
                    case 'inline':
                        $doc->addStyleDeclaration($style['content'], $style['type']);
                        break;
                }
            }
        }
    }

    protected static function registerScripts()
    {
        if (empty(self::$scripts['head'])) {
            return;
        }

        krsort(self::$scripts['head'], SORT_NUMERIC);

        $doc = \JFactory::getDocument();

        foreach (self::$scripts['head'] as $scripts) {
            foreach ($scripts as $script) {
                switch ($script[':type']) {
                    case 'file':
                        $doc->addScript($script['src'], $script['type'], $script['defer'], $script['async']);
                        break;
                    case 'inline':
                        $doc->addScriptDeclaration($script['content'], $script['type']);
                        break;
                }
            }
        }
    }

    protected static function registerJquery()
    {
        \JHtml::_('jquery.framework');
    }

    protected static function registerJqueryUiCore()
    {
        \JHtml::_('jquery.ui', ['core']);
    }

    protected static function registerJqueryUiSortable()
    {
        \JHtml::_('jquery.ui', ['sortable']);
    }

    protected static function registerBootstrap2()
    {
        Gantry::instance()['theme']->joomla(true);
    }

    protected static function registerMootools()
    {
        \JHtml::_('behavior.framework');
    }

    protected static function registerMootoolsMore()
    {
        \JHtml::_('behavior.framework', true);
    }

    /**
     * Override to support index.php?Itemid=xxx.
     *
     * @param array $matches
     * @return string
     * @internal
     */
    public static function linkHandler(array $matches)
    {
        $url = trim($matches[3]);
        if (strpos($url, 'index.php?') !== 0) {
            list($domain, $timestamp_age) = static::$urlFilterParams;
            $url = static::url(trim($matches[3]), $domain, $timestamp_age);
        }

        return "{$matches[1]}{$matches[2]}=\"{$url}\"";
    }
}
