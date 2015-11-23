<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */


namespace Gantry\Framework;

use Grav\Common\Grav;
use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
{
    public static function rootUri()
    {
        $grav = Grav::instance();
        return rtrim($grav['base_url'], '/');
    }


    public static function registerAssets()
    {
        static::registerStyles();
        static::registerScripts('head');
    }

    public static function registerStyles()
    {
        if (empty(self::$styles['head'])) {
            return;
        }

        $grav = Grav::instance();

        krsort(self::$styles['head'], SORT_NUMERIC);

        foreach (self::$styles['head'] as $priority => $styles) {
            foreach ($styles as $style) {
                switch ($style[':type']) {
                    case 'file':
                        $grav['assets']->addCss($style['href'], 100 + $priority);
                        break;
                    case 'inline':
                        $grav['assets']->addInlineCss($style['content'], 100 + $priority);
                        break;
                }
            }
        }
    }

    protected static function registerScripts($group)
    {
        if (empty(self::$scripts[$group])) {
            return;
        }

        $grav = Grav::instance();

        krsort(self::$scripts[$group], SORT_NUMERIC);

        foreach (self::$scripts[$group] as $priority => $scripts) {
            foreach ($scripts as $script) {
                switch ($script[':type']) {
                    case 'file':
                        $grav['assets']->AddJs($script['src'], [
                            'priority' => 100 + $priority,
                            'loading' => ($script['async'] ? 'async' : ($script['defer'] ? 'defer' : '')),
                            'group' => $group,
                        ]);
                        break;
                    case 'inline':
                        $grav['assets']->AddInlineJs($script['content'], [
                            'priority' => 100 + $priority,
                            'group' => $group,
                        ]);
                        break;
                }
            }
        }
    }
}
