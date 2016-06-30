<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Base;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Url\Url;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Document
{
    use GantryTrait;

    public static $timestamp_age = 604800;
    public static $urlFilterParams;

    protected static $stack = [];
    protected static $frameworks = [];
    protected static $scripts = [];
    protected static $styles = [];
    protected static $availableFrameworks = [
        'jquery' => 'registerJquery',
        'jquery.framework' => 'registerJquery',
        'jquery.ui.core' => 'registerJqueryUiSortable',
        'jquery.ui.sortable' => 'registerJqueryUiSortable',
        'bootstrap.2' => 'registerBootstrap2',
        'bootstrap.3' => 'registerBootstrap3',
        'mootools' => 'registerMootools',
        'mootools.framework' => 'registerMootools',
        'mootools.core' => 'registerMootools',
        'mootools.more' => 'registerMootoolsMore'
    ];

    /**
     * Create new local instance of document allowing asset caching.
     */
    public static function push()
    {
        array_push(static::$stack, ['frameworks' => static::$frameworks, 'scripts' => static::$scripts, 'styles' => static::$styles]);

    }

    /**
     * Return local instance of document allowing it to be cached.
     *
     * @return array
     */
    public static function pop()
    {
        $current = ['frameworks' => static::$frameworks, 'scripts' => static::$scripts, 'styles' => static::$styles];

        $old = array_pop(static::$stack);

        static::$frameworks = isset($old['frameworks']) ? $old['frameworks'] : [];
        static::$scripts = isset($old['scripts']) ? $old['scripts'] : [];
        static::$styles = isset($old['styles']) ? $old['styles'] : [];

        return $current;
    }

    /**
     * Append local assets to the outer document.
     *
     * @param array $document
     */
    public static function appendHeaderTags(array $document)
    {
        if (isset($document['frameworks'])) {
            static::$frameworks = static::appendArray(static::$frameworks, $document['frameworks']);
        }

        if (isset($document['scripts'])) {
            static::$scripts = static::appendArray(static::$scripts, $document['scripts']);
        }

        if (isset($document['styles'])) {
            static::$styles = static::appendArray(static::$styles, $document['styles']);
        }
    }

    public static function addHeaderTag(array $element, $location = 'head', $priority = 0)
    {
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href']) && !empty($element['rel']) && $element['rel'] == 'stylesheet') {
                    $id = !empty($element['id']) ? ['id' => $element['id']] : [];
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
                    ] + $id;

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
                    $handle = !empty($element['handle']) ? $element['handle'] : '';

                    static::$scripts[$location][$priority][md5($src).sha1($src)]= [
                        ':type' => 'file',
                        'src' => $src,
                        'type' => $type,
                        'defer' => $defer,
                        'async' => $async,
                        'handle' => $handle
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
        if (!isset(static::$styles[$location])) {
            return [];
        }

        $styles = static::$styles[$location];

        krsort($styles, SORT_NUMERIC);

        $html = [];

        foreach (static::$styles[$location] as $styles) {
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
        if (!isset(static::$scripts[$location])) {
            return [];
        }

        $scripts = static::$scripts[$location];

        krsort($scripts, SORT_NUMERIC);

        $html = [];

        foreach (static::$scripts[$location] as $scripts) {
            foreach ($scripts as $script) {
                switch ($script[':type']) {
                    case 'file':
                        $attribs = '';
                        if ($script['async']) {
                            $attribs .= ' async="async"';
                        }
                        if ($script['defer']) {
                            $attribs .= ' defer="defer"';
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

    public static function load($framework)
    {
        if (!isset(static::$availableFrameworks[$framework])) {
            return false;
        }

        static::$frameworks[] = $framework;

        return true;
    }

    public static function registerAssets()
    {
    }

    public static function siteUrl()
    {
        return static::rootUri();
    }

    /**
     * NOTE: In PHP this function can be called either from Gantry DI container or statically.
     *
     * @return string
     */
    public static function rootUri()
    {
        return '';
    }

    /**
     * NOTE: In PHP this function can be called either from Gantry DI container or statically.
     *
     * @param bool $addDomain
     * @return string
     */
    public static function domain($addDomain = false)
    {
        return '';
    }

    /**
     * Return URL to the resource.
     *
     * @example {{ url('gantry-theme://images/logo.png')|default('http://www.placehold.it/150x100/f4f4f4') }}
     *
     * NOTE: In PHP this function can be called either from Gantry DI container or statically.
     *
     * @param  string $url         Resource to be located.
     * @param  bool $domain        True to include domain name.
     * @param  int $timestamp_age  Append timestamp to files that are less than x seconds old. Defaults to a week.
     *                             Use value <= 0 to disable the feature.
     * @return string|null         Returns url to the resource or null if resource was not found.
     */
    public static function url($url, $domain = false, $timestamp_age = null)
    {
        if (!is_string($url) || $url === '') {
            // Return null on invalid input.
            return null;
        }

        if ($url[0] === '#') {
            // Handle fragment only.
            return $url;
        }

        $parts = Url::parse($url);

        if (!is_array($parts)) {
            // URL could not be parsed, return null.
            return null;
        }

        // Make sure we always have scheme, host, port and path.
        $scheme = isset($parts['scheme']) ? $parts['scheme'] : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? $parts['port'] : '';
        $path = isset($parts['path']) ? $parts['path'] : '';

        if ($scheme && !$port) {
            // If URL has a scheme, we need to check if it's one of Gantry streams.
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            if (!$locator->schemeExists($scheme)) {
                // If scheme does not exists as a stream, assume it's external.
                return $url;
            }

            // Attempt to find the resource (because of parse_url() we need to put host back to path).
            $path = $locator->findResource("{$scheme}://{$host}{$path}", false);

            if ($path === false) {
                return null;
            }

        } elseif ($host || $port) {
            // If URL doesn't have scheme but has host or port, it is external.
            return $url;
        }

        // At this point URL is either relative or absolute path; let us find if it is relative and not . or ..
        if ($path && '/' !== $path[0] && '.' !== $path[0]) {
            if ($timestamp_age === null) {
                $timestamp_age = static::$timestamp_age;
            }
            if ($timestamp_age > 0) {
                // We want to add timestamp to the URI: do it only for existing files.
                $realPath = @realpath(GANTRY5_ROOT . '/' . $path);
                if ($realPath && is_file($realPath)) {
                    $time = filemtime($realPath);
                    // Only append timestamp for files that are less than the maximum age.
                    if ($time > time() - $timestamp_age) {
                        $parts['query'] = (!empty($parts['query']) ? "{$parts['query']}&" : '') . sprintf('%x', $time);
                    }
                }
            }

            // We need absolute URI instead of relative.
            $path = rtrim(static::rootUri(), '/') . '/' . $path;
        }

        // Set absolute URI.
        $uri = $path;

        // Add query string back.
        if (!empty($parts['query'])) {
            if (!$uri) $uri = static::rootUri();
            $uri .= '?' . $parts['query'];
        }

        // Add fragment back.
        if (!empty($parts['fragment'])) {
            if (!$uri) $uri = static::rootUri();
            $uri .= '#' . $parts['fragment'];
        }

        return static::domain($domain) . $uri;
    }

    /**
     * Filter stream URLs from HTML.
     *
     * @param  string $html         HTML input to be filtered.
     * @param  bool $domain         True to include domain name.
     * @param  int $timestamp_age   Append timestamp to files that are less than x seconds old. Defaults to a week.
     *                              Use value <= 0 to disable the feature.
     * @return string               Returns modified HTML.
     */
    public static function urlFilter($html, $domain = false, $timestamp_age = null)
    {
        static::$urlFilterParams = [$domain, $timestamp_age];

        // Tokenize all PRE and CODE tags to avoid modifying any src|href|url in them
        $tokens = [];
        $html = preg_replace_callback('#<(pre|code).*?>.*?<\\/\\1>#is', function($matches) use (&$tokens) {
            $token = uniqid('__g5_token');
            $tokens['#' . $token . '#'] = $matches[0];

            return $token;
        }, $html);

        $html = preg_replace_callback('^(\s)(src|href)="(.*?)"^', 'static::linkHandler', $html);
        $html = preg_replace_callback('^(\s)url\((.*?)\)^', 'static::urlHandler', $html);
        $html = preg_replace(array_keys($tokens), array_values($tokens), $html); // restore tokens

        return $html;
    }

    /**
     * @param array $matches
     * @return string
     * @internal
     */
    public static function linkHandler(array $matches)
    {
        list($domain, $timestamp_age) = static::$urlFilterParams;
        $url = static::url(trim($matches[3]), $domain, $timestamp_age);

        return "{$matches[1]}{$matches[2]}=\"{$url}\"";
    }

    /**
     * @param array $matches
     * @return string
     * @internal
     */
    public static function urlHandler(array $matches)
    {
        list($domain, $timestamp_age) = static::$urlFilterParams;
        $url = static::url(trim($matches[2], '"\''), $domain, $timestamp_age);

        return "{$matches[1]}url({$url})";
    }

    /**
     * @param array $target
     * @param array $source
     * @return array
     */
    protected static function appendArray(array $target, array $source)
    {
        foreach ($source as $location => $priorities) {
            if (is_array($priorities)) {
                foreach ($priorities as $priority => $hashes) {
                    if (is_array($hashes)) {
                        foreach ($hashes as $hash => $element) {
                            $target[$location][$priority][$hash] = $element;
                        }
                    } else {
                            $target[$location][$priority] = $hashes;
                    }
                }
            } else {
                $target[$location] = $priorities;
            }
        }

        return $target;
    }

    /**
     * Register loaded frameworks.
     */
    protected static function registerFrameworks()
    {
        //print_r(static::$frameworks);die();
        foreach (static::$frameworks as $framework) {
            if (isset(static::$availableFrameworks[$framework])) {
                call_user_func([get_called_class(), static::$availableFrameworks[$framework]]);
            }
        }
    }

    protected static function registerJquery()
    {
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => 'https://code.jquery.com/jquery-2.2.2.min.js',
                'integrity' => 'sha256-36cp2Co+/62rEAAYHLmRCPIych47CvdM+uTBJwSzWjI=',
                'crossorigin' => 'anonymous'
            ],
            'head',
            10
        );
    }

    protected static function registerJqueryUiSortable()
    {
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => 'https://code.jquery.com/ui/1.11.4/jquery-ui.min.js',
                'integrity' => 'sha256-xNjb53/rY+WmG+4L6tTl9m6PpqknWZvRt0rO1SRnJzw=',
                'crossorigin' => 'anonymous'
            ],
            'head',
            10
        );
    }

    protected static function registerBootstrap2()
    {
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => 'https://maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js'
            ],
            'head',
            10
        );
    }

    protected static function registerBootstrap3()
    {
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js'
            ],
            'head',
            10
        );
    }

    protected static function registerMootools()
    {
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => 'https://cdnjs.cloudflare.com/ajax/libs/mootools/1.5.2/mootools-core-compat.min.js'
            ],
            'head',
            10
        );
    }

    protected static function registerMootoolsMore()
    {
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => 'https://cdnjs.cloudflare.com/ajax/libs/mootools-more/1.5.2/mootools-more-compat-compressed.js'
            ],
            'head',
            10
        );
    }
}
