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
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\CMS\WebAsset\WebAssetManager;

/**
 * Class Document
 * @package Gantry\Framework
 */
class Document extends HtmlDocument
{
    /**
     * @var array
     */
    protected static $availableFrameworks = [
        'jquery'             => 'registerJquery',
        'jquery.framework'   => 'registerJquery',
        'jquery.ui.core'     => 'registerJqueryUiCore',
        'jquery.ui.sortable' => 'registerJqueryUiSortable',
        'bootstrap'          => 'registerBootstrap',
        'bootstrap.2'        => 'registerBootstrap2',
        'bootstrap.3'        => 'registerBootstrap3',
        'bootstrap.4'        => 'registerBootstrap4',
        'bootstrap.5'        => 'registerBootstrap5',
        'mootools'           => 'registerMootools',
        'mootools.framework' => 'registerMootools',
        'mootools.core'      => 'registerMootools',
        'mootools.more'      => 'registerMootoolsMore',
        'lightcase'          => 'registerLightcase',
        'lightcase.init'     => 'registerLightcaseInit',
    ];

    /**
     * @param string $framework
     * @return bool
     */
    public static function addFramework($framework): bool
    {
        if (!parent::addFramework($framework)) {
            return false;
        }

        // Make sure that if Bootstap framework is loaded, also load CSS.
        if (
            $framework === 'bootstrap'
             || ($framework === 'bootstrap.5' && Version::MAJOR_VERSION >= 4)
        ) {
            $gantry = Gantry::instance();

            /** @var Theme $theme */
            $theme =  $gantry['theme'];
            $theme->joomla = true;
        }

        return true;
    }

    /**
     * @return void
     */
    public static function registerAssets(): void
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
    public static function domain($addDomain = null): string
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
    public static function rootUri(): string
    {
        return \rtrim(Uri::root(true), '/') ?: '/';
    }

    /**
     * @param bool|null $new
     * @return bool
     */
    public static function errorPage($new = null): bool
    {
        static $error = false;

        if (isset($new)) {
            $error = (bool) $new;
        }

        return $error;
    }

    /**
     * @return void
     */
    protected static function registerStyles(): void
    {
        if (static::errorPage()) {
            return;
        }

        $doc = Factory::getApplication()->getDocument();

        /** @var WebAssetManager $wa */
        $wa = $doc->getWebAssetManager();

        $styles = static::$stack[0]->getStyles();

        foreach ($styles as $style) {
            switch ($style[':type']) {
                case 'file':
                    $attribs = \array_replace([
                        'media' => $style['media'],
                    ], $style['element']);

                    $attribs = \array_filter($attribs, static function ($arg): bool {
                        return null !== $arg;
                    });

                    $uri   = \ltrim(\strtok($style['href'], '?'), '/');
                    $asset = $attribs['asset'] ?? 'template.' . \explode('_', \basename($uri, '.css'))[0];

                    $dependencies = isset($attribs['dependencies']) ? \explode(',', $attribs['dependencies']) : [];

                    if (\array_key_exists('dependencies', $attribs)) {
                        unset($attribs['dependencies']);
                    }

                    if (\array_key_exists('asset', $attribs)) {
                        unset($attribs['asset']);
                    }

                    // If the asset exists in our joomla.asset.json, then update the uri.
                    if ($wa->assetExists('style', $asset)) {
                        $item = $wa->getAsset('style', $asset);

                        // We are doing the forbiden here and updating the uri for the assest.
                        $rProperty = new \ReflectionProperty('Joomla\CMS\WebAsset\WebAssetItem', 'uri');
                        $rProperty->setAccessible(true);
                        $rProperty->setValue($item, $uri);

                        $wa->registerStyle($item);
                    } else {
                        $wa->registerAndUseStyle($asset, $uri, [], $attribs, $dependencies);
                    }

                    break;
                case 'inline':
                    $wa->addInlineStyle($style['content']);

                    break;
            }
        }
    }

    /**
     * @return void
     */
    protected static function registerScripts(): void
    {
        if (static::errorPage()) {
            return;
        }

        /** @var WebAssetManager $wa */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

        $scripts = static::$stack[0]->getScripts();

        foreach ($scripts as $script) {
            switch ($script[':type']) {
                case 'file':
                    $attribs = \array_replace([
                        'defer' => $script['defer'],
                        'async' => $script['async']
                    ], $script['element']);

                    unset($attribs['content']);

                    $attribs = \array_filter($attribs, static function ($arg): bool {
                        return null !== $arg;
                    });

                    $uri   = \strtok($script['src'], '?');
                    $uri   = \ltrim($uri, '/');

                    $asset        = $attribs['asset'] ?? 'template.' . basename($uri, '.js');
                    $dependencies = isset($attribs['dependencies']) ? \explode(',', $attribs['dependencies']) : [];

                    if (\array_key_exists('dependencies', $attribs)) {
                        unset($attribs['dependencies']);
                    }

                    if (\array_key_exists('asset', $attribs)) {
                        unset($attribs['asset']);
                    }

                    // If the asset exists in our joomla.asset.json, then update the uri.
                    if ($wa->assetExists('style', $asset)) {
                        $item = $wa->getAsset('style', $asset);

                        // We are doing the forbiden here and updating the uri for the assest.
                        $rProperty = new \ReflectionProperty('Joomla\CMS\WebAsset\WebAssetItem', 'uri');
                        $rProperty->setAccessible(true);
                        $rProperty->setValue($item, $uri);

                        $wa->registerScript($item);
                    } else {
                        $wa->registerAndUseScript($asset, $uri, [], $attribs, $dependencies);
                    }

                    break;
                case 'inline':
                    $wa->addInlineScript($script['content']);
                    break;
            }
        }
    }

    /**
     * @return void
     * @deprecated   5.6 will be removed in 5.7
     *               Will be removed without replacement
     */
    protected static function registerJquery(): void
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            if (!static::errorPage()) {
                HTMLHelper::_('jquery.framework');

                return;
            }

            /** @var WebAssetManager $wa */
            $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

            \array_map(
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
        }
    }

    /**
     * @return void
     * @deprecated   5.6 will be removed in 5.7
     *               Will be removed without replacement
     */
    protected static function registerJqueryUiCore(): void
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            @trigger_error('jQuery UI Core is not supported in Joomla 4, please remove the dependency!', E_USER_DEPRECATED);

            parent::registerJqueryUiSortable();

            return;
        }
    }

    /**
     * @return void
     * @deprecated   5.6 will be removed in 5.7
     *               Will be removed without replacement
     */
    protected static function registerJqueryUiSortable(): void
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            @trigger_error('jQuery UI Sortable is not supported in Joomla 4, please remove the dependency!', E_USER_DEPRECATED);

            parent::registerJqueryUiSortable();

            return;
        }
    }

    /**
     * @return void
     */
    protected static function registerBootstrap()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            static::registerBootstrap5();
        }
    }

    /**
     * @return void
     * @deprecated   5.6 will be removed in 5.7
     *               Will be removed without replacement
     */
    protected static function registerBootstrap2()
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            @trigger_error('Bootstrap 2 is not supported in Joomla 4, using Bootstrap 5 instead!', E_USER_DEPRECATED);

            static::registerBootstrap5();

            return;
        }
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

            \array_map(
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

    /**
     * @return void
     * @deprecated   5.6 will be removed in 5.7
     *               Will be removed without replacement
     */
    protected static function registerMootools(): void
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            @trigger_error('Mootools is no longer supported in Joomla 4!', E_USER_DEPRECATED);

            parent::registerMootools();

            return;
        }
    }

    /**
     * @return void
     * @deprecated   5.6 will be removed in 5.7
     *               Will be removed without replacement
     */
    protected static function registerMootoolsMore(): void
    {
        if (version_compare(JVERSION, '4.0', '>')) {
            @trigger_error('Mootools is no longer supported in Joomla 4!', E_USER_DEPRECATED);

            parent::registerMootoolsMore();

            return;
        }
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
        $url = \trim($matches[3]);

        if (\strpos($url, 'index.php?') !== 0) {
            [$domain, $timestamp_age] = static::$urlFilterParams;
            $url = static::url(\trim($matches[3]), $domain, $timestamp_age);
        }

        return "{$matches[1]}{$matches[2]}=\"{$url}\"";
    }

    /**
     * @param string $name
     * @param string $type
    */
    public static function webassetmanager($name, $type): void
    {
        if (!\in_array($type, ['style', 'script', 'preset'])) {
            return;
        }

        $app = Factory::getApplication();

        /** @var WebAssetManager $wa */
        $wa = $app->getDocument()->getWebAssetManager();
        $wr = $wa->getRegistry();

        if ($app->isClient('administrator')) {
            $wr->addRegistryFile('media/com_gantry5/joomla.asset.json');
        }

        if ($wa->assetExists($type, $name)) {
            $wa->useAsset($type, $name);
        }
    }

    /**
     * Adds `<link>` tags to the head of the document
     *
     * @param   string  $href      The link that is being related.
     * @param   string  $relation  Relation of link.
     * @param   ?string  $relType   Relation type attribute.  Either rel or rev (default: 'rel').
     * @param   ?array   $attribs   Associative array of remaining attributes.
     */
    public static function addHeadLink($href, $relation, $relType = 'rel', $attribs = []): void
    {
        Factory::getApplication()->getDocument()->addHeadLink($href, $relation, $relType, $attribs);
    }

    /**
     * Sets or alters a meta tag.
     *
     * @param   string  $name       Name of the meta HTML tag
     * @param   mixed   $content    Value of the meta HTML tag as array or string
     * @param   ?string  $attribute  Attribute to use in the meta HTML tag
     */
    public static function setMetaData($name, $content, $attribute = 'name'): void
    {
        Factory::getApplication()->getDocument()->setMetaData($name, $content, $attribute);
    }
}
