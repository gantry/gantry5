<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Base;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Document;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;
use RocketTheme\Toolbox\DI\Container;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */
abstract class Platform
{
    use NestedArrayAccess, Export;

    /** @var string */
    protected $name;
    /** @var array */
    protected $features = [];
    /** @var string */
    protected $settings_key;
    /** @var array */
    protected $items;
    /** @var Container */
    protected $container;

    /**
     * Platform constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        //Make sure that cache folder exists, otherwise it will be removed from the lookup.
        $cachePath = $this->getCachePath();
        Folder::create($cachePath);

        $this->items = [
            'streams' => [
                // Cached files.
                'gantry-cache' => [
                    'type' => 'Stream',
                    'force' => true,
                    'prefixes' => ['' => [$cachePath]]
                ],
                // Container for all frontend themes.
                'gantry-themes' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getThemesPaths()
                ],
                // Selected frontend theme.
                'gantry-theme' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getThemePaths()
                ],
                // System defined media files.
                'gantry-assets' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getAssetsPaths()
                ],
                // User defined media files.
                'gantry-media' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getMediaPaths()
                ],
                // Container for all Gantry engines.
                'gantry-engines' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getEnginesPaths()
                ],
                // Gantry engine used to render the selected theme.
                'gantry-engine' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getEnginePaths()
                ],
                // Layout definitions for the blueprints.
                'gantry-layouts' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => ['' => ['gantry-theme://layouts', 'gantry-engine://layouts']]
                ],
                // Gantry particles.
                'gantry-particles' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => ['' => ['gantry-theme://particles', 'gantry-engine://particles']]
                ],
                // Gantry administration.
                'gantry-admin' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => []
                ],
                // Blueprints for the configuration.
                'gantry-blueprints' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['gantry-theme://blueprints', 'gantry-engine://blueprints'],
                        'particles' => ['gantry-particles://']
                    ]
                ],
                // Configuration from the selected theme.
                'gantry-config' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => ['' => ['gantry-theme://config']]
                ]
            ]
        ];
    }

    /**
     * Gets version of CMS.
     *
     * @return string
     */
    abstract public function getVersion();

    /**
     * Compares version of CMS against the given version.
     *
     * @param string $version Lower bound (>=)
     * @param string|null $version2 Upper bound (<)
     * @return bool True if version matches, false otherwise.
     */
    public function checkVersion($version, $version2 = null)
    {
        $cmsVersion = $this->getVersion();

        return version_compare($cmsVersion, $version, '>=') && (null === $version2 || version_compare($cmsVersion, $version2, '<'));
    }

    abstract public function getCachePath();
    abstract public function getThemesPaths();
    abstract public function getAssetsPaths();
    abstract public function getMediaPaths();

    /**
     * @return $this
     */
    public function init()
    {
        return $this;
    }

    /**
     * @param string $feature
     * @return bool
     */
    public function has($feature)
    {
        return !empty($this->features[$feature]);
    }

    /**
     * @return array
     */
    public function getThemePaths()
    {
        return ['' => []];
    }

    /**
     * @param string $name
     * @return array
     */
    public function getEnginePaths($name = 'nucleus')
    {
        return ['' => ['gantry-theme://engine', "gantry-engines://{$name}"]];
    }

    /**
     * @return array
     */
    public function getEnginesPaths()
    {
        return ['' => []];
    }

    /**
     * @return array
     */
    public function errorHandlerPaths()
    {
        return [];
    }

    /**
     * Get preview url for individual theme.
     *
     * @param string $theme
     * @return string|null
     */
    abstract public function getThemePreviewUrl($theme);

    /**
     * Get administrator url for individual theme.
     *
     * @param string $theme
     * @return string|null
     */
    abstract public function getThemeAdminUrl($theme);

    /**
     * @return null
     */
    public function settings()
    {
        return null;
    }

    /**
     * @return string
     */
    public function settings_key()
    {
        return $this->settings_key;
    }

    /**
     * @return array|bool
     */
    public function listModules()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @param string $content
     * @param string|int|null $width
     * @param string|int|null $height
     * @return mixed|null
     */
    public function getEditor($name, $content = '', $width = null, $height = null)
    {
        return null;
    }

    /**
     * @param string $text
     * @return string
     */
    public function filter($text)
    {
        return $text;
    }

    public function finalize()
    {
        $gantry = Gantry::instance();
        /** @var Document $document */
        $document = $gantry['document'];

        $document::registerAssets();
    }

    /**
     * @return mixed|null
     */
    public function call()
    {
        $args = func_get_args();
        $callable = array_shift($args);
        return is_callable($callable) ? call_user_func_array($callable, $args) : null;
    }

    /**
     * @param string $action
     * @param int|string|null $id
     * @return bool
     */
    public function authorize($action, $id = null)
    {
        return true;
    }

    /**
     * @param array|string $dependencies
     * @return bool
     * @since 5.4.3
     */
    public function checkDependencies($dependencies)
    {
        if (is_string($dependencies)) {
            return $dependencies === $this->name;
        }

        if (isset($dependencies['platform'])) {
            if (is_string($dependencies['platform']) && $dependencies['platform'] !== $this->name) {
                return false;
            }
            if (!isset($dependencies['platform'][$this->name])) {
                return false;
            }
        }

        return true;
    }
}
