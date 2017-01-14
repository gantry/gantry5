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

namespace Gantry\Framework\Base;

use Gantry\Component\Filesystem\Folder;
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

    protected $name;
    protected $features = [];
    protected $settings_key;
    protected $items;
    protected $container;

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

    abstract public function getCachePath();
    abstract public function getThemesPaths();
    abstract public function getAssetsPaths();
    abstract public function getMediaPaths();

    public function init()
    {
        return $this;
    }

    public function has($feature)
    {
        return !empty($this->features[$feature]);
    }

    public function getThemePaths()
    {
        return ['' => []];
    }

    public function getEnginePaths($name = 'nucleus')
    {
        return ['' => ['gantry-theme://engine', "gantry-engines://{$name}"]];
    }

    public function getEnginesPaths()
    {
        return ['' => []];
    }

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

    public function settings()
    {
        return null;
    }

    public function settings_key()
    {
        return $this->settings_key;
    }

    public function listModules()
    {
        return false;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEditor($name, $content = '', $width = null, $height = null)
    {
        return null;
    }

    public function filter($text)
    {
        return $text;
    }

    public function finalize()
    {
        $gantry = Gantry::instance();

        $gantry['document']->registerAssets();
    }

    public function call()
    {
        $args = func_get_args();
        $callable = array_shift($args);
        return is_callable($callable) ? call_user_func_array($callable, $args) : null;
    }

    public function authorize($action)
    {
        return true;
    }

    /**
     * @param array|string $dependencies
     * @return bool|null
     * @since 5.4.3
     */
    public function checkDependencies($dependencies)
    {
        if (is_string($dependencies) && $dependencies !== $this->name) {
            return false;
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
