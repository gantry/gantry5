<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Content\Document\HtmlDocument;

class Document extends HtmlDocument
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
        'mootools.more' => 'registerMootoolsMore',
        'lightcase' => 'registerLightcase',
        'lightcase.init' => 'registerLightcaseInit',
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

    public static function errorPage($new = null)
    {
        static $error = false;

        if (isset($new)) {
            $error = (bool) $new;
        }

        return $error;
    }

    protected static function registerStyles()
    {
        if (static::errorPage()) {
            return;
        }

        $doc = \JFactory::getDocument();

        $styles = static::$stack[0]->getStyles();

        foreach ($styles as $style) {
            switch ($style[':type']) {
                case 'file':
                    $doc->addStyleSheet($style['href'], $style['type'], $style['media'], $style['element']);
                    break;
                case 'inline':
                    $doc->addStyleDeclaration($style['content'], $style['type']);
                    break;
            }
        }
    }

    protected static function registerScripts()
    {
        if (static::errorPage()) {
            return;
        }

        $doc = \JFactory::getDocument();

        $scripts = static::$stack[0]->getScripts();

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

    protected static function registerJquery()
    {
        if (!static::errorPage()) {
            \JHtml::_('jquery.framework');

            return;
        }

        // Workaround for error document type.
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/jui/js/jquery.min.js'
            ],
            'head',
            100
        );
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/jui/js/jquery-noconflict.js'
            ],
            'head',
            100
        );
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/jui/js/jquery-migrate.min.js'
            ],
            'head',
            100
        );
    }

    protected static function registerJqueryUiCore()
    {
        if (!static::errorPage()) {
            \JHtml::_('jquery.ui', ['core']);

            return;
        }

        // Workaround for error document type.
        static::registerJquery();
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/jui/js/jquery.ui.core.min.js'
            ],
            'head',
            100
        );

    }

    protected static function registerJqueryUiSortable()
    {
        if (!static::errorPage()) {
            \JHtml::_('jquery.ui', ['sortable']);

            return;
        }

        // Workaround for error document type.
        static::registerJqueryUiCore();
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/jui/js/jquery.ui.sortable.min.js'
            ],
            'head',
            100
        );
    }

    protected static function registerBootstrap2()
    {
        Gantry::instance()['theme']->joomla(true);

        if (!static::errorPage()) {
            \JHtml::_('bootstrap.framework');

            return;
        }

        // Workaround for error document type.
        static::registerJquery();
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/jui/js/bootstrap.min.js'
            ],
            'head',
            100
        );
    }

    protected static function registerMootools()
    {
        if (!static::errorPage()) {
            \JHtml::_('behavior.framework');

            return;
        }

        // Workaround for error document type.
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/system/js/mootools-core.js'
            ],
            'head',
            99
        );
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/system/js/core.js'
            ],
            'head',
            99
        );
    }

    protected static function registerMootoolsMore()
    {
        if (!static::errorPage()) {
            \JHtml::_('behavior.framework', true);

            return;
        }

        // Workaround for error document type.
        static::registerMootools();
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => \JUri::getInstance()->base(true) . '/media/system/js/mootools-more.js'
            ],
            'head',
            99
        );
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
