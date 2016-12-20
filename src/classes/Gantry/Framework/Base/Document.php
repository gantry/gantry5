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
        'mootools.more' => 'registerMootoolsMore',
        'lightcase' => 'registerLightcase',
        'lightcase.init' => 'registerLightcaseInit',
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

    /**
     * @param string $framework
     * @return bool
     */
    public static function addFramework($framework)
    {
        if (!isset(static::$availableFrameworks[$framework])) {
            return false;
        }

        static::$frameworks[] = $framework;

        return true;
    }

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     */
    public static function addStyle($element, $priority = 0, $location = 'head')
    {
        if (!is_array($element)) {
            $element = ['href' => $element];
        }
        if (empty($element['href'])) {
            return false;
        }

        $id = !empty($element['id']) ? ['id' => $element['id']] : [];
        $href = $element['href'];
        $type = !empty($element['type']) ? $element['type'] : 'text/css';
        $media = !empty($element['media']) ? $element['media'] : null;
        unset($element['tag'], $element['id'], $element['rel'], $element['content'], $element['href'], $element['type'], $element['media']);

        static::$styles[$location][(int) $priority][md5($href).sha1($href)] = [
            ':type' => 'file',
            'href' => $href,
            'type' => $type,
            'media' => $media,
            'element' => $element
        ] + $id;

        return true;
    }

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     */
    public static function addInlineStyle($element, $priority = 0, $location = 'head')
    {
        if (!is_array($element)) {
            $element = ['content' => $element];
        }
        if (empty($element['content'])) {
            return false;
        }

        $content = $element['content'];
        $type = !empty($element['type']) ? $element['type'] : 'text/css';

        static::$styles[$location][(int) $priority][md5($content).sha1($content)] = [
            ':type' => 'inline',
            'content' => $content,
            'type' => $type
        ];

        return true;
    }

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     */
    public static function addScript($element, $priority = 0, $location = 'head')
    {
        if (!is_array($element)) {
            $element = ['src' => $element];
        }
        if (empty($element['src'])) {
            return false;
        }

        $src = $element['src'];
        $type = !empty($element['type']) ? $element['type'] : 'text/javascript';
        $defer = isset($element['defer']) ? true : false;
        $async = isset($element['async']) ? true : false;
        $handle = !empty($element['handle']) ? $element['handle'] : '';

        static::$scripts[$location][(int) $priority][md5($src) . sha1($src)] = [
            ':type' => 'file',
            'src' => $src,
            'type' => $type,
            'defer' => $defer,
            'async' => $async,
            'handle' => $handle
        ];

        return true;
    }

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     */
    public static function addInlineScript($element, $priority = 0, $location = 'head')
    {
        if (!is_array($element)) {
            $element = ['content' => $element];
        }
        if (empty($element['content'])) {
            return false;
        }

        $content = $element['content'];
        $type = !empty($element['type']) ? $element['type'] : 'text/javascript';

        static::$scripts[$location][(int) $priority][md5($content).sha1($content)] = [
            ':type' => 'inline',
            'content' => $content,
            'type' => $type
        ];

        return true;
    }

    /**
     * @param array $element
     * @param string $location
     * @param int $priority
     * @return bool
     */
    public static function addHeaderTag(array $element, $location = 'head', $priority = 0)
    {
        $success = false;

        switch ($element['tag']) {
            case 'link':
                if (!empty($element['rel']) && $element['rel'] === 'stylesheet') {
                    $success = static::addStyle($element, $priority, $location);
                }

                break;

            case 'style':
                $success = static::addInlineStyle($element, $priority, $location);

                break;

            case 'script':
                if (!empty($element['src'])) {
                    $success = static::addScript($element, $priority, $location);
                } elseif (!empty($element['content'])) {
                    $success = static::addInlineScript($element, $priority, $location);
                }

                break;
        }

        return $success;
    }

    public static function getStyles($location = 'head')
    {
        if (!isset(static::$styles[$location])) {
            return [];
        }

        krsort(static::$styles[$location], SORT_NUMERIC);

        $html = [];

        foreach (static::$styles[$location] as $styles) {
            foreach ($styles as $style) {
                switch ($style[':type']) {
                    case 'file':
                        $attribs = '';
                        if ($style['media']) {
                            $attribs .= ' media="' . static::escape($style['media']) . '"';
                        }
                        $html[] = sprintf(
                            '<link rel="stylesheet" href="%s" type="%s"%s />',
                            static::escape($style['href']),
                            static::escape($style['type']),
                            $attribs
                        );
                        break;
                    case 'inline':
                        $attribs = '';
                        if ($style['type'] !== 'text/css') {
                            $attribs .= ' type="' . static::escape($style['type']) . '"';
                        }
                        $html[] = sprintf(
                            '<style%s>%s</style>', 
                            $attribs, 
                            $style['content']
                        );
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

        krsort(static::$scripts[$location], SORT_NUMERIC);

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
                        $html[] = sprintf(
                            '<script type="%s"%s src="%s"></script>',
                            static::escape($script['type']),
                            $attribs,
                            static::escape($script['src'])
                        );
                        break;
                    case 'inline':
                        $html[] = sprintf(
                            '<script type="%s">%s</script>',
                            static::escape($script['type']),
                            $script['content']
                        );
                        break;
                }
            }
        }

        return $html;
    }

    /**
     * Escape string (emulates twig filter).
     *
     * @param string $string
     * @return string
     */
    public static function escape($string, $strategy = 'html')
    {
        if (!is_string($string)) {
            if (is_object($string) && method_exists($string, '__toString')) {
                $string = (string) $string;
            } elseif (in_array($strategy, array('html', 'js', 'css', 'html_attr', 'url'))) {
                return $string;
            }
        }

        switch ($strategy) {
            case 'html':
                return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            case 'js':
                if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
                    throw new \RuntimeException('The string to escape is not a valid UTF-8 string.');
                }

                $string = preg_replace_callback('#[^a-zA-Z0-9,\._]#Su', '_twig_escape_js_callback', $string);

                return $string;

            case 'css':
                if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
                    throw new \RuntimeException('The string to escape is not a valid UTF-8 string.');
                }

                $string = preg_replace_callback('#[^a-zA-Z0-9]#Su', '_twig_escape_css_callback', $string);

                return $string;

            case 'html_attr':
                if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
                    throw new \RuntimeException('The string to escape is not a valid UTF-8 string.');
                }

                $string = preg_replace_callback('#[^a-zA-Z0-9,\.\-_]#Su', '_twig_escape_html_attr_callback', $string);

                return $string;

            case 'url':
                return rawurlencode($string);

            default:
                throw new \RuntimeException(sprintf('Invalid escaping strategy "%s" (valid ones: html, js, css, html_attr, url).', $strategy));
        }
    }

    /**
     * @param $framework
     * @return bool
     * @deprecated 5.3
     */
    public static function load($framework)
    {
        return static::addFramework($framework);
    }

    /**
     * Register assets.
     */
    public static function registerAssets()
    {
        static::registerFrameworks();
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
     * @param  bool $allowNull     True if non-existing files should return null.
     * @return string|null         Returns url to the resource or null if resource was not found.
     */
    public static function url($url, $domain = false, $timestamp_age = null, $allowNull = true)
    {
        if (!is_string($url) || $url === '') {
            // Return null on invalid input.
            return null;
        }

        if ($url[0] === '#' || $url[0] === '?') {
            // Handle urls with query string or fragment only.
            return $url;
        }

        $parts = Url::parse($url);

        if (!is_array($parts)) {
            // URL could not be parsed.
            return $allowNull ? null : $url;
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
            $newPath = $locator->findResource("{$scheme}://{$host}{$path}", false);

            if ($newPath === false) {
                if ($allowNull) {
                    return null;
                }

                // Return location where the file would be if it was saved.
                $path = $locator->findResource("{$scheme}://{$host}{$path}", false, true);
            } else {
                $path = $newPath;
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
     * @param  bool $streamOnly     Only touch streams.
     * @return string               Returns modified HTML.
     */
    public static function urlFilter($html, $domain = false, $timestamp_age = null, $streamOnly = false)
    {
        static::$urlFilterParams = [$domain, $timestamp_age, $streamOnly];

        // Tokenize all PRE and CODE tags to avoid modifying any src|href|url in them
        $tokens = [];
        $html = preg_replace_callback('#<(pre|code).*?>.*?<\\/\\1>#is', function($matches) use (&$tokens) {
            $token = uniqid('__g5_token');
            $tokens['#' . $token . '#'] = $matches[0];

            return $token;
        }, $html);

        if ($streamOnly) {
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];
            $schemes = $locator->getSchemes();

            $list = [];
            foreach ($schemes as $scheme) {
                if (strpos($scheme, 'gantry-') === 0) {
                    $list[] = substr($scheme, 7);
                }
            }
            if (empty($list)) {
                return $html;
            }

            $match = '(gantry-(' . implode('|', $list). ')://.*?)';
        } else {
            $match = '(.*?)';
        }

        $html = preg_replace_callback('^(\s)(src|href)="' . $match . '"^', 'static::linkHandler', $html);
        $html = preg_replace_callback('^(\s)url\(' . $match . '\)^', 'static::urlHandler', $html);
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
        $url = trim($matches[3]);
        $url = static::url($url, $domain, $timestamp_age, false);

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
        $url = trim($matches[2], '"\'');
        $url = static::url($url, $domain, $timestamp_age, false);

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
        foreach (static::$frameworks as $framework) {
            if (isset(static::$availableFrameworks[$framework])) {
                call_user_func([get_called_class(), static::$availableFrameworks[$framework]]);
            }
        }
    }

    protected static function registerJquery()
    {
        static::addScript(
            [
                'src' => 'https://code.jquery.com/jquery-2.2.2.min.js',
                'integrity' => 'sha256-36cp2Co+/62rEAAYHLmRCPIych47CvdM+uTBJwSzWjI=',
                'crossorigin' => 'anonymous'
            ],
            11
        );
    }

    protected static function registerJqueryUiSortable()
    {
        static::registerJquery();

        static::addScript(
            [
                'src' => 'https://code.jquery.com/ui/1.11.4/jquery-ui.min.js',
                'integrity' => 'sha256-xNjb53/rY+WmG+4L6tTl9m6PpqknWZvRt0rO1SRnJzw=',
                'crossorigin' => 'anonymous'
            ],
            11
        );
    }

    protected static function registerBootstrap2()
    {
        static::registerJquery();

        static::addScript(['src' => 'https://maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js'], 11);
    }

    protected static function registerBootstrap3()
    {
        static::registerJquery();

        static::addScript(['src' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js'], 11);
    }

    protected static function registerMootools()
    {
        static::addScript(['src' => 'https://cdnjs.cloudflare.com/ajax/libs/mootools/1.5.2/mootools-core-compat.min.js'], 11);
    }

    protected static function registerMootoolsMore()
    {
        static::registerMootools();

        static::addScript(['src' => 'https://cdnjs.cloudflare.com/ajax/libs/mootools-more/1.5.2/mootools-more-compat-compressed.js'], 11);
    }

    protected static function registerLightcase()
    {
        static::registerJquery();

        static::addScript(['src' => self::url('gantry-assets://js/lightcase.js', false, null, false)], 11, 'footer');
        static::addStyle(['href' => self::url('gantry-assets://css/lightcase.min.css', false, null, false)], 11);
    }

    protected static function registerLightcaseInit()
    {
        static::registerLightcase();

        static::addInlineScript(['content' => "jQuery(document).ready(function($) { jQuery('[data-rel^=lightcase]').lightcase(); });"], 0, 'footer');
    }
}
