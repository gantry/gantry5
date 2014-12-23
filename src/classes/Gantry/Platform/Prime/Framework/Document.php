<?php
namespace Gantry\Framework;

class Document
{
    public static $styles = [];
    public static $scripts = ['header' => [], 'footer' => []];

    public static function addHeaderTag(array $element, $in_footer = false)
    {
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href']) && !empty($element['rel']) && $element['rel'] == 'stylesheet') {
                    $href = $element['href'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    $media = !empty($element['media']) ? $element['media'] : null;
                    unset($element['tag'], $element['rel'], $element['content'], $element['href'], $element['type'], $element['media']);
                    self::$styles[$href] = "<link rel=\"stylesheet\" href=\"{$href}\" type=\"{$type}\"" . ($media ? " media=\"{$media}\"" : '') . " />";
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
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';
                    self::$scripts[$location][$src] = "<script type=\"{$type}\" src=\"{$src}\"></script>";
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
