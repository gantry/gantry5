<?php
namespace Gantry\Framework;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
{
    public static $wp_styles = [];
    public static $wp_scripts = [];

    public static function registerAssets()
    {
        static::registerStyles();
        static::registerScripts('head');
        static::registerScripts('footer');
    }

    protected static function registerStyles()
    {
        if (empty(self::$styles['head'])) {
            return;
        }

        krsort(self::$styles['head'], SORT_NUMERIC);

        foreach (self::$styles['head'] as $styles) {
            foreach ($styles as $style) {
                switch ($style[':type']) {
                    case 'file':
                        $href = preg_replace('/(\?.*)$/', '', $style['href']);
                        \wp_register_style(basename($href, '.css'), $href, array(), false, $style['media']);
                        \wp_enqueue_style(basename($href, '.css'));
                        break;
                    case 'inline':
                        if (is_admin()) {
                            $type = !empty($style['type']) ? $style['type'] : 'text/css';
                            self::$wp_styles[] = "<style type=\"{$type}\">{$style['content']}</style>";
                        } else {
                            \wp_add_inline_style( md5($style['content']), $style['content'] );
                        }
                        break;
                }
            }
        }

        self::$styles['head'] = [];
    }

    protected static function registerScripts($pos)
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
                        $src = preg_replace('/(\?.*)$/', '', $script['src']);
                        \wp_register_script(basename($src, '.js'), $src, array(), false, $in_footer);
                        \wp_enqueue_script(basename($src, '.js'));
                        break;
                    case 'inline':
                        if (is_admin()) {
                            $type = !empty($script['type']) ? $script['type'] : 'text/css';
                            self::$wp_scripts[] = "<script type=\"{$type}\">{$script['content']}</script>";
                        } else {
                            \wp_add_inline_script( md5($script['content']), $script['content'] );
                        }
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
}
