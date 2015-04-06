<?php
namespace Gantry\Framework;

use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
{
    public static $styles = [];
    public static $scripts = ['header' => [], 'footer' => []];

    public static function addHeaderTag(array $element, $in_footer = false)
    {
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href']) && !empty($element['rel']) && $element['rel'] == 'stylesheet') {
                    $attrs = [];
                    $href = $element['href'];
                    unset($element['tag'], $element['href'], $element['rel'], $element['content']);

                    foreach($element as $name => $value) {
                        if (!$value) {
                            if ($name == 'type') { $value = 'text/css'; }
                            if ($name == 'media') { $value = null; }
                        }

                        $attrs[] = $name . '="' . $value . '"';
                    }

                    self::$styles[$href] = '<link rel="stylesheet" href="' . $href . '" ' . implode(" ", $attrs) . ' />';
                    return true;
                }
                break;

            case 'style':
                if (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    self::$styles[md5($content).sha1($content)] = "<style type=\"{$type}\">{$content}</style>";
                    return true;
                }
                break;

            case 'script':
                $location = $in_footer ? 'footer' : 'header';
                if (!empty($element['src'])) {
                    $src = $element['src'];
                    unset($element['tag'], $element['src'], $element['content']);

                    foreach($element as $name => $value) {
                        if (!$value && $name == 'type') { $value = 'text/javascript'; }

                        $attrs[] = $name . '="' . $value . '"';
                    }

                    self::$scripts[$location][$src] = '<script src="' . $src . '" ' . implode(" ", $attrs) .'></script>';
                    return true;

                } elseif (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';
                    self::$scripts[$location][md5($content).sha1($content)] = "<script type=\"{$type}\">{$content}</script>";
                    return true;
                }
                break;
        }
        return false;
    }

    public static function rootUri()
    {
        return PRIME_URI;
    }
}
