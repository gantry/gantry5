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

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
{
    public static $wp_styles = [];
    public static $wp_scripts = ['head' => [], 'footer' => []];
    protected static $script_info = [];

    public static function registerAssets()
    {
        static::registerStyles();
        static::registerScripts('head');
        static::registerScripts('footer');
    }

    public static function registerStyles()
    {
        if (empty(self::$styles['head'])) {
            return;
        }

        krsort(self::$styles['head'], SORT_NUMERIC);

        foreach (self::$styles['head'] as $styles) {
            foreach ($styles as $style) {
                switch ($style[':type']) {
                    case 'file':
                        $array = explode('?', $style['href']);
                        $href = array_shift($array);
                        $version = array_shift($array) ?: false;
                        $name = basename($href, '.css');
                        \wp_enqueue_style($name, $href, array(), $version, $style['media']);
                        break;
                    case 'inline':
                        $type = !empty($style['type']) ? $style['type'] : 'text/css';
                        self::$wp_styles[] = "<style type=\"{$type}\">{$style['content']}</style>";
                        break;
                }
            }
        }

        self::$styles['head'] = [];
    }

    public static function registerScripts($pos)
    {
        if (empty(self::$scripts[$pos])) {
            return;
        }

        $in_footer = ($pos != 'head');
        krsort(self::$scripts[$pos], SORT_NUMERIC);

        foreach (self::$scripts[$pos] as $scripts) {
            foreach ($scripts as $script) {
                switch ($script[':type']) {
                    case 'file':
                        $array = explode('?', $script['src']);
                        $src = array_shift($array);
                        $version = array_shift($array) ?: false;
                        $name = basename($src, '.js');
                        if(!empty($script['handle'])) {
                            $name = $script['handle'];
                        }
                        self::$script_info[$name] = $script;
                        \wp_enqueue_script($name, $src, array(), $version, $in_footer);
                        break;
                    case 'inline':
                        $type = !empty($script['type']) ? $script['type'] : 'text/javascript';
                        self::$wp_scripts[$pos][] = "<script type=\"{$type}\">{$script['content']}</script>";
                        break;
                }
            }
        }

        self::$scripts[$pos] = [];
    }

    public static function domain($addDomain = false)
    {
        static $domain;

        if (!isset($domain)) {
            $url = \get_site_url();
            $components = parse_url($url);

            $scheme = isset($components['scheme']) ? $components['scheme'] . '://' : '';
            $host = isset($components['host']) ? $components['host'] : '';
            $port = isset($components['port']) ? ':' . $components['port'] : '';
            $user = isset($components['user']) ? $components['user'] : '';
            $pass = isset($components['pass']) ? ':' . $components['pass']  : '';
            $pass = ($user || $pass) ? "$pass@" : '';
            $domain = $scheme . $user . $pass . $host . $port;
        }

        // Always append domain in WP.
        return $domain;
    }

    public static function siteUrl()
    {
        return \get_site_url();
    }

    public static function rootUri()
    {
        static $path;

        if (!isset($path)) {
            $url = \get_site_url();
            $components = parse_url($url);

            $path = !empty($components['path']) ? $components['path'] : '/';
        }

        return $path;
    }

    public static function script_add_attributes($tag, $handle)
    {
        if (!isset(self::$script_info[$handle])) {
            return $tag;
        }

        $script = self::$script_info[$handle];

        $append = [];
        if ($script['defer']) {
            $append[] = 'defer="defer"';
        }
        if ($script['async']) {
            $append[] = 'async="async"';
        }

        if (!$append) {
            return $tag;
        }

        $append = implode(' ', $append);

        return str_replace(' src=', " {$append} src=", $tag);
    }
}
