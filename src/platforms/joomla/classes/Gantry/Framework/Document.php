<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Content\Document\HtmlDocument;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version as JVersion;
use Joomla\CMS\WebAsset\WebAssetManager;

/**
 * Class Document
 * @package Gantry\Framework
 */
class Document extends HtmlDocument
{
    protected static $availableFrameworks = [
        'jquery' => 'registerJquery',
        'jquery.framework' => 'registerJquery',
        'jquery.ui.core' => 'registerJqueryUiCore',
        'jquery.ui.sortable' => 'registerJqueryUiSortable',
        'bootstrap' => 'registerBootstrap',
        'bootstrap.2' => 'registerBootstrap2',
        'bootstrap.3' => 'registerBootstrap3',
        'bootstrap.4' => 'registerBootstrap4',
        'bootstrap.5' => 'registerBootstrap5',
        'mootools' => 'registerMootools',
        'mootools.framework' => 'registerMootools',
        'mootools.core' => 'registerMootools',
        'mootools.more' => 'registerMootoolsMore',
        'lightcase' => 'registerLightcase',
        'lightcase.init' => 'registerLightcaseInit',
    ];

    /**
     * @param string $framework
     * @return bool
     */
    public static function addFramework($framework)
    {
        if (!parent::addFramework($framework)) {
            return false;
        }

        // Make sure that if Bootstap framework is loaded, also load CSS.
        if (
            $framework === 'bootstrap'
            || ($framework === 'bootstrap.2' && JVersion::MAJOR_VERSION === 3)
            || ($framework === 'bootstrap.5' && JVersion::MAJOR_VERSION >= 4)
        ) {
            /** @var Theme $theme */
            $theme = Gantry::instance()['theme'];
            $theme->joomla = true;
        }

        return true;
    }

    /**
     *
     */
    public static function registerAssets()
    {
        static::registerFrameworks();
        static::registerStyles();
        static::registerScripts();
    }

    /**
     * NOTE: In PHP this function can be called either from Gantry DI container or statically.
     *
     * @param bool|null $addDomain
     * @return string
     */
    public static function domain($addDomain = null)
    {
        if (!$addDomain) {
            return '';
        }

        $absolute = Uri::root(false);
        $relative = Uri::root(true);

        return substr($absolute, 0, -strlen($relative));
    }

    /**
     * @return string
     */
    public static function rootUri()
    {
        return rtrim(Uri::root(true), '/') ?: '/';
    }

    /**
     * @param bool|null $new
     * @return bool
     */
    public static function errorPage($new = null)
    {
        static $error = false;

        if (isset($new)) {
            $error = (bool) $new;
        }

        return $error;
    }

    protected static function registerStyles()
    {
        if (static::errorPage()) {
            return;
        }

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $doc = $application->getDocument();

        $styles = static::$stack[0]->getStyles();

        foreach ($styles as $style) {
            switch ($style[':type']) {
                case 'file':
                    $attribs = array_replace(['type' => $style['type'], 'media' => $style['media']], $style['element']);
                    $attribs = array_filter($attribs, static function($arg) { return null !== $arg; });
                    $doc->addStyleSheet($style['href'], [], $attribs);
                    break;
                case 'inline':
                    $doc->addStyleDeclaration($style['content'], $style['type']);
                    break;
            }
        }
    }

    protected static function registerScripts()
    {
        if (static::errorPage()) {
            return;
        }

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $doc = $application->getDocument();

        $scripts = static::$stack[0]->getScripts();

        foreach ($scripts as $script) {
            switch ($script[':type']) {
                case 'file':
                    $attribs = ['mime' => $script['type'], 'defer' => $script['defer'], 'async' => $script['async']];
                    $attribs = array_filter($attribs, static function($arg) { return null !== $arg; });
                    $doc->addScript($script['src'], [], $attribs);
                    break;
                case 'inline':
                    $doc->addScriptDeclaration($script['content'], $script['type']);
                    break;
            }
        }
    }

    protected static function registerJquery()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            if (!static::errorPage()) {
                HTMLHelper::_('jquery.framework');

                return;
            }

            /** @var WebAssetManager $wa */
            $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

            array_map(
                static function ($script) use ($wa) {
                    $asset = $wa->getAsset('script', $script);

                    // Workaround for error document type.
                    static::addHeaderTag(
                        [
                            'tag' => 'script',
                            'src' => $asset->getUri(true)
                        ],
                        'head',
                        100
                    );
                },
                ['jquery', 'jquery-noconflict']
            );

            return;
        }

        // Joomla 3:
        if (!static::errorPage()) {
            HTMLHelper::_('jquery.framework');

            return;
        }

        // Workaround for error document type.
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/jui/js/jquery.min.js'
            ],
            'head',
            100
        );
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/jui/js/jquery-noconflict.js'
            ],
            'head',
            100
        );
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/jui/js/jquery-migrate.min.js'
            ],
            'head',
            100
        );
    }

    protected static function registerJqueryUiCore()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            //user_error('jQuery UI Core is not supported in Joomla 4, please remove the dependency!', E_USER_DEPRECATED);

            parent::registerJqueryUiSortable();

            return;
        }

        if (!static::errorPage()) {
            HTMLHelper::_('jquery.ui', ['core']);

            return;
        }

        // Workaround for error document type.
        static::registerJquery();
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/jui/js/jquery.ui.core.min.js'
            ],
            'head',
            100
        );

    }

    protected static function registerJqueryUiSortable()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            //user_error('jQuery UI Sortable is not supported in Joomla 4, please remove the dependency!', E_USER_DEPRECATED);

            parent::registerJqueryUiSortable();

            return;
        }

        if (!static::errorPage()) {
            HTMLHelper::_('jquery.ui', ['sortable']);

            return;
        }

        // Workaround for error document type.
        static::registerJqueryUiCore();
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/jui/js/jquery.ui.sortable.min.js'
            ],
            'head',
            100
        );
    }

    protected static function registerBootstrap()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            static::registerBootstrap5();
        } else {
            static::registerBootstrap2();
        }
    }

    protected static function registerBootstrap2()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            //user_error('Bootstrap 2 is not supported in Joomla 4, using Bootstrap 5 instead!', E_USER_DEPRECATED);

            static::registerBootstrap5();

            return;
        }

        if (!static::errorPage()) {
            HTMLHelper::_('bootstrap.framework');

            return;
        }

        // Workaround for error document type.
        static::registerJquery();
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/jui/js/bootstrap.min.js'
            ],
            'head',
            100
        );
    }

    protected static function registerBootstrap5()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            if (!static::errorPage()) {
                HTMLHelper::_('bootstrap.framework');

                return;
            }

            /** @var WebAssetManager $wa */
            $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

            array_map(
                static function ($script) use ($wa) {
                    $asset = $wa->getAsset('script', 'bootstrap.' . $script);

                    // Workaround for error document type.
                    static::addHeaderTag(
                        [
                            'tag' => 'script',
                            'src' => $asset->getUri(true) . '?' . $asset->getVersion()
                        ],
                        'head',
                        100
                    );
                },
                ['alert', 'button', 'carousel', 'collapse', 'dropdown', 'modal', 'offcanvas', 'popover', 'scrollspy', 'tab', 'toast']
            );

            return;
        }

        parent::registerBootstrap5();
    }

    protected static function registerMootools()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            //user_error('Mootools is no longer supported in Joomla 4!', E_USER_DEPRECATED);

            parent::registerMootools();

            return;
        }

        if (!static::errorPage()) {
            HTMLHelper::_('behavior.framework');

            return;
        }

        // Workaround for error document type.
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/system/js/mootools-core.js'
            ],
            'head',
            99
        );
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/system/js/core.js'
            ],
            'head',
            99
        );
    }

    protected static function registerMootoolsMore()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            //user_error('Mootools is no longer supported in Joomla 4!', E_USER_DEPRECATED);

            parent::registerMootoolsMore();

            return;
        }

        if (!static::errorPage()) {
            HTMLHelper::_('behavior.framework', true);

            return;
        }

        // Workaround for error document type.
        static::registerMootools();
        static::addHeaderTag(
            [
                'tag' => 'script',
                'src' => Uri::getInstance()->base(true) . '/media/system/js/mootools-more.js'
            ],
            'head',
            99
        );
    }

    /**
     * Override to support index.php?Itemid=xxx.
     *
     * @param array $matches
     * @return string
     * @internal
     */
    public static function linkHandler(array $matches)
    {
        $url = trim($matches[3]);
        if (strpos($url, 'index.php?') !== 0) {
            list($domain, $timestamp_age) = static::$urlFilterParams;
            $url = static::url(trim($matches[3]), $domain, $timestamp_age);
        }

        return "{$matches[1]}{$matches[2]}=\"{$url}\"";
    }
}
