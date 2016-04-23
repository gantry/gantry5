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
    public static function registerAssets()
    {
        static::registerStyles();
        static::registerScripts();
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

    public static function load($framework)
    {
        switch ($framework) {
            case 'jquery':
            case 'jquery.framework':
                \JHtml::_('jquery.framework');
                break;
            case 'jquery.ui.core':
                \JHtml::_('jquery.ui', ['core']);
                break;
            case 'jquery.ui.sortable':
                \JHtml::_('jquery.ui', ['sortable']);
                break;
            case 'boostrap.2':
                Gantry::instance()['theme']->joomla(true);
                break;
            case 'mootools':
            case 'mootools.framework':
            case 'mootools.core':
                \JHtml::_('behavior.framework');
                break;
            case 'mootools.more':
                \JHtml::_('behavior.framework', true);
                break;
            default:
                return false;
        }

        return true;
    }

    public static function rootUri()
    {
        return rtrim(\JUri::root(true), '/') ?: '/';
    }
}
