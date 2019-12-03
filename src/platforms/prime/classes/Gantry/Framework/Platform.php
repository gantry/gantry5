<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Position\Module;
use Gantry\Component\Position\Position;
use Gantry\Debugger;
use Gantry\Framework\Base\Platform as BasePlatform;
use RocketTheme\Toolbox\DI\Container;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    /** @var string */
    protected $name = 'prime';
    /** @var string */
    protected $settings_key = '';

    /**
     * Platform constructor.
     * @param Container $container
     */
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

    /**
     * @return string
     */
    public function getCachePath()
    {
        return GANTRY5_ROOT . '/cache';
    }

    /**
     * @return array
     */
    public function getThemesPaths()
    {
        return  ['' => ['themes']];
    }

    /**
     * @return array
     */
    public function getEnginesPaths()
    {
        if (is_link(GANTRY5_ROOT . '/engines')) {
            // Development environment.
            return ['' => ["engines/{$this->name}", 'engines/common']];
        }
        return ['' => ['engines']];
    }

    /**
     * @return array
     */
    public function getAssetsPaths()
    {
        if (is_link(GANTRY5_ROOT . '/assets')) {
            // Development environment.
            return ['' => ['gantry-theme://', "assets/{$this->name}", 'assets/common']];
        }
        return ['' => ['gantry-theme://', 'assets']];
    }

    /**
     * @return array
     */
    public function getMediaPaths()
    {
        /** @var Config $global */
        $global = $this->container['global'];

        $paths = ['media'];

        if ($global->get('use_media_folder', false)) {
            $paths[] = 'gantry-theme://images';
        } else {
            array_unshift($paths, 'gantry-theme://images');
        }

        return ['' => $paths];
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
        return rtrim(PRIME_URI, '/') . '/' . $theme . '/admin/configurations/default/styles';
    }

    /**
     * @param $position
     * @return int
     */
    public function countModules($position)
    {
        return count($this->getModules($position));
    }

    /**
     * @param $position
     * @return array
     */
    public function getModules($position)
    {
        return (new Position($position))->listModules();
    }

    /**
     * @param string|array $id
     * @param array $attribs
     * @return string
     */
    public function displayModule($id, $attribs = [])
    {
        $module = is_array($id) ? $id : $this->getModule($id);

        // Make sure that module really exists.
        if (!$module || !is_array($module)) {
            return '';
        }

        if (isset($module['assignments'])) {
            $assignments = $module['assignments'];
            $outline = Gantry::instance()['configuration'];

            if (is_array($assignments) && !in_array($outline, ['_error', '_offline'])) {
                // TODO: move Assignments to DI to speed it up.
                $matches = (new Assignments)->matches(['test' => $assignments]);
                if (GANTRY_DEBUGGER) {
                    Debugger::addMessage("Module assignments for '{$module['id']}' (rules, matches):", 'debug');
                    Debugger::addMessage($assignments, 'debug');
                    Debugger::addMessage(isset($matches['test']) ? $matches['test'] : [], 'debug');
                }
                if (!$matches) {
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

    /**
     * @param string $position
     * @param array $attribs
     * @return string
     */
    public function displayModules($position, $attribs = [])
    {
        $html = '';
        foreach ($this->getModules($position) as $module) {
            $html .= $this->displayModule($module, $attribs);
        }

        return $html;
    }

    /**
     * @param string $id
     * @return array
     */
    protected function getModule($id)
    {
        list($position, $module) = explode('/', $id, 2);

        return (new Module($module, $position))->toArray();
    }

    /**
     * @return null
     */
    public function settings()
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function settings_key()
    {
        return null;
    }

    /**
     * @param string $text
     * @param int $length
     * @param bool $html
     */
    public function truncate($text, $length, $html = false)
    {
        // TODO:
        throw new \Exception('Not implemented');
    }
}
