<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Position\Module;
use Gantry\Component\Position\Position;
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
        return ['' => ['media', 'gantry-theme://images']];
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

    public function countModules($position)
    {
        return count($this->getModules($position));
    }

    public function getModules($position)
    {
        return (new Position($position))->listModules();
    }

    public function displayModule($id, $attribs = [])
    {
        $module = is_array($id) ? $id : $this->getModule($id);

        // Make sure that module really exists.
        if (!$module || !is_array($module)) {
            return '';
        }

        if (isset($module['assignments'])) {
            $assignments = $module['assignments'];
            if (is_array($assignments)) {
                // TODO: move Assignments to DI to speed it up.
                if (!(new Assignments)->matches(['test' => $assignments])) {
                    return '';
                }
            } elseif ($assignments !== 'all') {
                return '';
            }
        }

        /** @var Theme $theme */
        $theme = $this->container['theme'];

        $html = trim($theme->render('@nucleus/partials/module.html.twig', $attribs + ['segment' => $module]));

        return $html;
    }

    public function displayModules($position, $attribs = [])
    {
        $html = '';
        foreach ($this->getModules($position) as $module) {
            $html .= $this->displayModule($module, $attribs);
        }

        return $html;
    }

    protected function getModule($id)
    {
        list($position, $module) = explode('/', $id, 2);

        return (new Module($module, $position))->toArray();
    }

    public function settings()
    {
        return null;
    }

    public function settings_key()
    {
        return null;
    }

    public function truncate($text, $length, $html = false)
    {
        // TODO:
        throw new \Exception('Not implemented');
    }
}
