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

    public static function addHeaderTag(array $element, $in_footer = false)
    {
        $doc = \JFactory::getDocument();
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href']) && !empty($element['rel']) && $element['rel'] == 'stylesheet') {
                    $href = $element['href'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    $media = !empty($element['media']) ? $element['media'] : null;
                    unset($element['tag'], $element['rel'], $element['content'], $element['href'], $element['type'], $element['media']);
                    $doc->AddStyleSheet($href, $type, $media, $element);
                    return true;
                }
                break;

            case 'style':
                if (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    $doc->addStyleDeclaration($content, $type);
                    return true;
                }
                break;

            case 'script':
                if (!empty($element['src'])) {
                    $src = $element['src'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';
                    if ($in_footer) {
                       self::$scripts['footer'][$src] = "<script type=\"{$type}\" src=\"{$src}\"></script>";
                    } else {
                        $doc->addScript($src, $type);
                    }
                    return true;

                } elseif (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';
                    if ($in_footer) {
                       self::$scripts['footer'][md5($content).sha1($content)] = "<script type=\"{$type}\">{$content}</script>";
                    } else {
                        $doc->addScriptDeclaration($content, $type);
                    }
                    return true;
                }
                break;
        }
        return false;
    }

    public static function rootUri()
    {
        return rtrim(\JUri::root(true), '/');
    }
}
