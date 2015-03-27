<?php
namespace Gantry\Framework;

use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
{
    public static $styles = array();
    public static $scripts = array();

    public static function addHeaderTag(array $element, $in_footer = false)
    {
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href']) && !empty($element['rel']) && $element['rel'] == 'stylesheet') {
                    $href = $element['href'];
                    $media = !empty($element['media']) ? $element['media'] : null;
                    \wp_register_style(basename($href, '.css'), $href, array(), false, $media);
                    \wp_enqueue_style(basename($href, '.css'));
                    return true;
                }
                break;

            case 'style':
                if (!empty($element['content'])) {
                    $content = $element['content'];
                    if (is_admin()) {
                        $type = !empty($element['type']) ? $element['type'] : 'text/css';
                        self::$styles[] = "<style type=\"{$type}\">{$content}</style>";
                    } else {
                        \wp_add_inline_style( md5($content), $content );
                    }
                    return true;
                }
                break;

            case 'script':
                if (!empty($element['src'])) {
                    $src = $element['src'];
                    \wp_register_script(basename($src, '.js'), $src, array(), false, $in_footer);
                    \wp_enqueue_script(basename($src, '.js'));
                    return true;

                } elseif (!empty($element['content'])) {
                    $content = $element['content'];
                    if (is_admin()) {
                        $type = !empty($element['type']) ? $element['type'] : 'text/css';
                        self::$scripts[] = "<script type=\"{$type}\">{$content}</script>";
                    } else {
                        \wp_add_inline_script( md5($content), $content );
                    }
                    return true;
                }
                break;
        }
        return false;
    }

    public static function rootUri()
    {
        return \get_site_url();
    }
}
