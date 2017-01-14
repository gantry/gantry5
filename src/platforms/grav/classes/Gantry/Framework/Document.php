<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Content\Document\HtmlDocument;
use Grav\Common\Config\Config;
use Grav\Common\Grav;
use Grav\Common\Language\Language;

class Document extends HtmlDocument
{
    public static function registerAssets()
    {
        static::registerFrameworks();
        static::registerStyles();
        static::registerScripts('head');
    }

    public static function rootUri()
    {
        $grav = Grav::instance();

        return rtrim($grav['base_url'], '/') ?: '/';
    }

    public static function siteUrl()
    {
        static $url;

        if (!$url) {
            // TODO: there should be Grav method to get this!
            $grav = Grav::instance();

            /** @var Config $config */
            $config = $grav['config'];

            /** @var Language $language */
            $language = $grav['language'];

            $active_language = $language->getActive();

            $path_append = rtrim($grav['pages']->base(), '/');
            if ($language->getDefault() != $active_language || $config->get('system.languages.include_default_lang') === true) {
                $path_append .= $active_language ? '/' . $active_language : '';
            }

            $url = rtrim($grav['base_url'] . $path_append, '/') ?: '/';
        }

        return $url;
    }

    public static function registerStyles()
    {
        $grav = Grav::instance();

        $styles = static::$stack[0]->getStyles();

        foreach ($styles as $style) {
            switch ($style[':type']) {
                case 'file':
                    $grav['assets']->addCss(static::getRelativeUrl($style['href']), 90 + $style[':priority']);
                    break;
                case 'inline':
                    $grav['assets']->addInlineCss($style['content'], 90 + $style[':priority']);
                    break;
            }
        }
    }

    protected static function registerScripts($group)
    {
        $grav = Grav::instance();

        $scripts = static::$stack[0]->getScripts($group);

        foreach ($scripts as $script) {
            switch ($script[':type']) {
                case 'file':
                    $grav['assets']->AddJs(static::getRelativeUrl($script['src']), [
                        'priority' => 90 + $script[':priority'],
                        'loading' => ($script['async'] ? 'async' : ($script['defer'] ? 'defer' : '')),
                        'group' => $group,
                    ]);
                    break;
                case 'inline':
                    $grav['assets']->AddInlineJs($script['content'], [
                        'priority' => 90 + $script[':priority'],
                        'group' => $group,
                    ]);
                    break;
            }
        }
    }

    protected static function getRelativeUrl($url)
    {
        $base = rtrim(static::rootUri(), '/') . '/';

        if (strpos($url, $base) === 0) {
            return substr($url, strlen($base) - 1);
        }
        return $url;
    }

    protected static function registerJquery()
    {
        Grav::instance()['assets']->addJs('jquery', 111);
    }
}
