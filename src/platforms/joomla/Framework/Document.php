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
    public static $scripts = ['header' => [], 'footer' => []];
    public static $styles = ['header' => [], 'footer' => []];

    public static function addHeaderTag(array $element, $in_footer = false, $priority = 0)
    {
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href']) && !empty($element['rel']) && $element['rel'] == 'stylesheet') {
                    $href = $element['href'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    $media = !empty($element['media']) ? $element['media'] : null;
                    unset($element['tag'], $element['rel'], $element['content'], $element['href'], $element['type'], $element['media']);

                    static::$styles['header'][$priority][$href] = [
                        ':type' => 'file',
                        'href' => $href,
                        'type' => $type,
                        'media' => $media,
                        'element' => $element
                    ];

                    return true;
                }
                break;

            case 'style':
                if (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';

                    static::$styles['header'][$priority][md5($content).sha1($content)] = [
                        ':type' => 'inline',
                        'content' => $content,
                        'type' => $type
                    ];

                    return true;
                }
                break;

            case 'script':
                if (!empty($element['src'])) {
                    $src = $element['src'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';
                    $defer = isset($element['defer']) ? true : false;
                    $async = isset($element['async']) ? true : false;

                    if ($in_footer) {
                        static::$scripts['footer'][$priority][$src] = "<script type=\"{$type}\" src=\"{$src}\"></script>";
                    } else {
                        static::$scripts['header'][$priority][$src]= [
                            ':type' => 'file',
                            'src' => $src,
                            'type' => $type,
                            'defer' => $defer,
                            'async' => $async
                        ];
                    }
                    return true;

                } elseif (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';

                    if ($in_footer) {
                        static::$scripts['footer'][$priority][md5($content).sha1($content)] = "<script type=\"{$type}\">{$content}</script>";
                    } else {
                        static::$scripts['header'][$priority][md5($content).sha1($content)] = [
                            ':type' => 'inline',
                            'content' => $content,
                            'type' => $type
                        ];
                    }

                    return true;
                }
                break;
        }
        return false;
    }

    public static function registerAssets()
    {
        static::registerStyles();
        static::registerScripts();
    }

    protected static function registerStyles()
    {
        krsort(self::$styles['header'], SORT_NUMERIC);

        $doc = \JFactory::getDocument();

        foreach (self::$styles['header'] as $styles) {
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
        krsort(self::$scripts['header'], SORT_NUMERIC);

        $doc = \JFactory::getDocument();

        foreach (self::$scripts['header'] as $scripts) {
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
