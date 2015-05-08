<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Base;

use Gantry\Component\Gantry\GantryTrait;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Document
{
    use GantryTrait;

    protected static $scripts = [];
    protected static $styles = [];

    public static function addHeaderTag(array $element, $location = 'head', $priority = 0)
    {
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href']) && !empty($element['rel']) && $element['rel'] == 'stylesheet') {
                    $href = $element['href'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    $media = !empty($element['media']) ? $element['media'] : null;
                    unset($element['tag'], $element['rel'], $element['content'], $element['href'], $element['type'], $element['media']);

                    static::$styles[$location][$priority][md5($href).sha1($href)] = [
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

                    static::$styles[$location][$priority][md5($content).sha1($content)] = [
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

                    static::$scripts[$location][$priority][md5($src).sha1($src)]= [
                        ':type' => 'file',
                        'src' => $src,
                        'type' => $type,
                        'defer' => $defer,
                        'async' => $async
                    ];

                    return true;

                } elseif (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';

                    static::$scripts[$location][$priority][md5($content).sha1($content)] = [
                        ':type' => 'inline',
                        'content' => $content,
                        'type' => $type
                    ];

                    return true;
                }
                break;
        }
        return false;
    }

    public static function getStyles($location = 'head')
    {
        if (!isset(self::$styles[$location])) {
            return [];
        }

        $styles = self::$styles[$location];

        krsort($styles, SORT_NUMERIC);

        $html = [];

        foreach (self::$styles[$location] as $styles) {
            foreach ($styles as $style) {
                switch ($style[':type']) {
                    case 'file':
                        $attribs = '';
                        if ($style['media']) {
                            $attribs .= ' media="' . $style['media'] . '"';
                        }
                        $html[] = "<link rel=\"stylesheet\" href=\"{$style['href']}\" type=\"{$style['type']}\"{$attribs} />";
                        break;
                    case 'inline':
                        $attribs = '';
                        if ($style['type'] !== 'text/css') {
                            $attribs .= ' type="' . $style['type'] . '"';
                        }
                        $html[] = "<style{$attribs}>{$style['content']}</style>";
                        break;
                }
            }
        }

        return $html;
    }

    public static function getScripts($location = 'head')
    {
        if (!isset(self::$scripts[$location])) {
            return [];
        }

        $scripts = self::$scripts[$location];

        krsort($scripts, SORT_NUMERIC);

        $html = [];

        foreach (self::$scripts[$location] as $scripts) {
            foreach ($scripts as $script) {
                switch ($script[':type']) {
                    case 'file':
                        $attribs = '';
                        if ($script['async']) {
                            $attribs .= ' async="async"';
                        }
                        if ($script['defer']) {
                            $attribs .= ' async="defer"';
                        }
                        $html[] = "<script type=\"{$script['type']}\"{$attribs} src=\"{$script['src']}\"></script>";
                        break;
                    case 'inline':
                        $html[] = "<script type=\"{$script['type']}\">{$script['content']}</script>";
                        break;
                }
            }
        }

        return $html;
    }

    public static function rootUri()
    {
        return '';
    }

    /**
     * Return URL to the resource.
     *
     * @example {{ url('theme://images/logo.png')|default('http://www.placehold.it/150x100/f4f4f4') }}
     *
     * @param  string $url    Resource to be located.
     * @param  bool $domain     True to include domain name.
     * @param  bool $timestamp  True to append timestamp for the existing files.
     * @return string|null      Returns url to the resource or null if resource was not found.
     */
    public static function url($url, $domain = false, $timestamp = true)
    {
        if (!$url) {
            return null;
        }

        if ($url[0] == '/') {
            // Absolute path in our server.
            // TODO: add support to include domain..
            return $url;

        }

        // Remove fragment for stream / file lookup.
        $parts = explode('#', $url, 2);
        $uri = array_shift($parts);
        $fragment = array_shift($parts);

        // Remove parameters for stream / file lookup.
        $parts = explode('?', $uri, 2);
        $uri = array_shift($parts);
        $params = array_shift($parts);

        if (strpos($uri, '://') !== false) {
            // Resolve stream to a relative path.
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            try {
                // Attempt to find our resource.
                $uri = $locator->findResource($uri, false);
                if (!$uri) {
                    // Resource not found.
                    return null;
                }
            } catch (\Exception $e) {
                // Scheme did not exist; assume that we had valid scheme (like http) so no modification is needed.
                return $url;
            }
        }

        if ($timestamp && $uri) {
            // We want to add timestamp to the URL: do it only for local files.
            $realPath = realpath(GANTRY5_ROOT . '/' . $uri);
            if ($realPath) {
                $params = $params ? "{$params}&" : '';
                $params .= sprintf('%x', filemtime($realPath));
            }
        }

        // Add parameters back.
        if ($params) {
            $uri .= '?' . $params;
        }

        // Add fragment back.
        if ($fragment) {
            $uri .= '#' . $fragment;
        }

        // TODO: add support to include domain..
        return rtrim(static::rootUri(), '/') . '/' . $uri;
    }
}
