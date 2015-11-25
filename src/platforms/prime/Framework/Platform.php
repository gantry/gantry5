<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Platform as BasePlatform;
use RocketTheme\Toolbox\DI\Container;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    protected $name = 'prime';
    protected $settings_key = '';

    public function __construct(Container $container)
    {
        parent::__construct($container);

        Folder::create(GANTRY5_ROOT . '/custom');

        // Initialize custom streams for Prime.
        $this->items['streams'] += [
            'gantry-prime' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => ['']
                ]
            ],
            'gantry-custom' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => []
                ]
            ],
            'gantry-pages' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => ['gantry-theme://overrides/pages', 'pages']
                ]
            ],
            'gantry-positions' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => ['gantry-theme://overrides/positions', 'positions']
                ]
            ]
        ];

        $this->items['streams']['gantry-layouts']['prefixes'][''][] = 'gantry-prime://layouts';
        $this->items['streams']['gantry-config']['prefixes'][''][] = 'gantry-prime://config';
    }

    public function getCachePath()
    {
        return GANTRY5_ROOT . '/cache';
    }

    public function getThemesPaths()
    {
        return  ['' => ['themes']];
    }

    public function getEnginesPaths()
    {
        if (is_link(GANTRY5_ROOT . '/engines')) {
            // Development environment.
            return ['' => ["engines/{$this->name}", "engines/common"]];
        }
        return ['' => ['engines']];
    }

    public function getAssetsPaths()
    {
        if (is_link(GANTRY5_ROOT . '/assets')) {
            // Development environment.
            return ['' => ['gantry-theme://', "assets/{$this->name}", 'assets/common']];
        }
        return ['' => ['gantry-theme://', 'assets']];
    }

    public function getMediaPaths()
    {
        return ['' => ['media']];
    }

    /**
     * Get preview url for individual theme.
     *
     * @param string $theme
     * @return string
     */
    public function getThemePreviewUrl($theme)
    {
        return rtrim(PRIME_URI, '/') . '/' . $theme;
    }

    /**
     * Get administrator url for individual theme.
     *
     * @param string $theme
     * @return string
     */
    public function getThemeAdminUrl($theme)
    {
        return rtrim(PRIME_URI, '/') . '/' . $theme . '/admin/configurations/styles';
    }

    public function getModules($position)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $finder = new ConfigFileFinder;
        $files = $finder->listFiles($locator->findResources('gantry-positions://' . $position), '|\.html\.twig|', 0);

        return array_keys($files);
    }

    public function settings()
    {
        return null;
    }

    public function settings_key()
    {
        return null;
    }
}
