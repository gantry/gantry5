<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
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

    public static function rootUri()
    {
        return rtrim(\JUri::root(true), '/');
    }
}
