<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Content\Document;

use Gantry\Component\Content\Block\ContentBlock;
use Gantry\Component\Content\Block\HtmlBlock;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Url\Url;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class HtmlDocument
{
    use GantryTrait;

    public static $timestamp_age = 604800;
    public static $urlFilterParams;

    /**
     * @var array|HtmlBlock[]
     */
    protected static $stack;
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

    public function __construct()
    {
        static::$stack = [];
        static::push();
    }

    /**
     * Create new local instance of document allowing asset caching.
     */
    public static function push()
    {
        array_unshift(static::$stack, new HtmlBlock());
    }

    /**
     * Return local instance of document allowing it to be cached.
     *
     * @return HtmlBlock
     */
    public static function pop()
    {
        return array_shift(static::$stack);
    }

    /**
     * @param ContentBlock $block
     * @return $this
     */
    public function addBlock(ContentBlock $block)
    {
        static::$stack[0]->addBlock($block);

        return $this;
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

        static::getObject();
        static::$stack[0]->addFramework($framework);

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
        static::getObject();
        return static::$stack[0]->addStyle($element, $priority, $location);
    }

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     */
    public static function addInlineStyle($element, $priority = 0, $location = 'head')
    {
        static::getObject();
        return static::$stack[0]->addInlineStyle($element, $priority, $location);
    }

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     */
    public static function addScript($element, $priority = 0, $location = 'head')
    {
        static::getObject();
        return static::$stack[0]->addScript($element, $priority, $location);
    }

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     */
    public static function addInlineScript($element, $priority = 0, $location = 'head')
    {
        static::getObject();
        return static::$stack[0]->addInlineScript($element, $priority, $location);
    }

    /**
     * @param string $html
     * @param int $priority
     * @param string $location
     * @return bool
     */
    public static function addHtml($html, $priority = 0, $location = 'bottom')
    {
        static::getObject();
        return static::$stack[0]->addHtml($html, $priority, $location);
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
        static::getObject();
        $styles = static::$stack[0]->getStyles($location);

        $output = [];

        foreach ($styles as $style) {
            switch ($style[':type']) {
                case 'file':
                    $attribs = '';
                    if ($style['media']) {
                        $attribs .= ' media="' . static::escape($style['media']) . '"';
                    }
                    $output[] = sprintf(
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
                    $output[] = sprintf(
                        '<style%s>%s</style>',
                        $attribs,
                        $style['content']
                    );
                    break;
            }
        }

        return $output;
    }

    public static function getScripts($location = 'head')
    {
        static::getObject();
        $scripts = static::$stack[0]->getScripts($location);

        $output = [];

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
                    $output[] = sprintf(
                        '<script type="%s"%s src="%s"></script>',
                        static::escape($script['type']),
                        $attribs,
                        static::escape($script['src'])
                    );
                    break;
                case 'inline':
                    $output[] = sprintf(
                        '<script type="%s">%s</script>',
                        static::escape($script['type']),
                        $script['content']
                    );
                    break;
            }
        }

        return $output;
    }

    public static function getHtml($location = 'bottom')
    {
        static::getObject();
        $htmls = static::$stack[0]->getHtml($location);
        $output = [];

        foreach ($htmls as $html) {
            $output[] = $html['html'];
        }

        return $output;
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
            return str_replace(' ', '%20', $url);
        }

        $parts = Url::parse($url);

        if (!is_array($parts)) {
            // URL could not be parsed.
            return $allowNull ? null : str_replace(' ', '%20', $url);
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
                return str_replace(' ', '%20', $url);
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
            return str_replace(' ', '%20', $url);
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

        return static::domain($domain) . str_replace(' ', '%20', $uri);
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

        // Tokenize all PRE, CODE and SCRIPT tags to avoid modifying any src|href|url in them
        $tokens = [];

        $html = preg_replace_callback('#<(pre|code|script)(\s[^>]+)?>.*?</\\1>#ius', function($matches) use (&$tokens) {
            // Unfortunately uniqid() doesn't quite work in Windows, so we need to work it around by adding some randomness.
            $token = '@@'. uniqid(mt_rand(), true) . '@@';
            $match = $matches[0];

            $tokens[$token] = $match;

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

        $html = preg_replace_callback('^(\s)(src|href)="' . $match . '"^u', 'static::linkHandler', $html);
        $html = preg_replace_callback('^(\s)url\(' . $match . '\)^u', 'static::urlHandler', $html);
        $html = static::replaceTokens($tokens, $html);

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
     * Replace tokens with strings.
     *
     * @param array $tokens
     * @param $html
     * @return string|NUll
     */
    protected static function replaceTokens(array $tokens, $html)
    {
        foreach ($tokens as $token => $replacement) {
            // We need to use callbacks to turn off backreferences ($1, \\1) in the replacement string.
            $callback = function() use ($replacement) { return $replacement; };

            $html = preg_replace_callback('#' . preg_quote($token, '#') . '#u', $callback, $html);
        }

        return $html;
    }

    /**
     * Register loaded frameworks.
     */
    protected static function registerFrameworks()
    {
        foreach (static::$stack[0]->getFrameworks() as $framework) {
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

        static::addScript(['src' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'], 11);
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

        static::addScript(['src' => static::url('gantry-assets://js/lightcase.js', false, null, false)], 11, 'footer');
        static::addStyle(['href' => static::url('gantry-assets://css/lightcase.min.css', false, null, false)], 11);
    }

    protected static function registerLightcaseInit()
    {
        static::registerLightcase();

        static::addInlineScript(['content' => "jQuery(document).ready(function($) { jQuery('[data-rel^=lightcase]').lightcase(); });"], 0, 'footer');
    }

    protected static function getObject()
    {
        static $object;

        if (!$object) {
            // We need to initialize document for backwards compatibility (RokSprocket/RokGallery in WP).
            $object = Gantry::instance()['document'];
        }

        return $object;
    }
}
