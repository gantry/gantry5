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

use Gantry\Component\Position\Module;
use Gantry\Component\Position\Position;
use Gantry\Framework\Base\Platform as BasePlatform;
use Grav\Common\Grav;
use Grav\Common\Utils;
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
    protected $name = 'grav';
    protected $features = ['fontawesome' => false];

    public function __construct(Container $container)
    {
        parent::__construct($container);

        // Initialize custom streams for Prime.
        $this->items['streams'] += [
            'gantry-positions' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => [
                        'user://data/gantry5/positions',
                        'user://positions' // TODO: remove
                    ]
                ]
            ]
        ];
    }

    /**
     * @return string
     */
    public function getCachePath()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        return $locator->findResource('cache://gantry5', true, true);
    }

    /**
     * @return array
     */
    public function getThemesPaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        return $locator->getPaths('themes');
    }

    public function getMediaPaths()
    {
        $paths = ['image://'];

        if ($this->container['global']->get('use_media_folder', false)) {
            array_push($paths, 'gantry-theme://images');
        } else {
            array_unshift($paths, 'gantry-theme://images');
        }

        return ['' => $paths];
    }

    public function getEnginesPaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        if (is_link($locator('plugin://gantry5/engines'))) {
            // Development environment.
            return ['' => ["plugin://gantry5/engines/{$this->name}", 'plugin://gantry5/engines/common']];
        }
        return ['' => ['plugin://gantry5/engines']];
    }

    public function getAssetsPaths()
    {
        $grav = Grav::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        if (is_link($locator('plugin://gantry5/assets'))) {
            // Development environment.
            return ['' => ['gantry-theme://', "plugin://gantry5/assets/{$this->name}", 'plugin://gantry5/assets/common']];
        }

        return ['' => ['gantry-theme://', 'plugin://gantry5/assets']];
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
            $outline = Gantry::instance()['configuration'];

            if (is_array($assignments) && !in_array($outline, ['_error', '_offline'])) {
                // TODO: move Assignments to DI to speed it up.
                $matches = (new Assignments)->matches(['test' => $assignments]);
                if (GANTRY_DEBUGGER) {
                    \Gantry\Debugger::addMessage("Module assignments for '{$module['id']}' (rules, matches):", 'debug');
                    \Gantry\Debugger::addMessage($assignments, 'debug');
                    \Gantry\Debugger::addMessage(isset($matches['test']) ? $matches['test'] : [], 'debug');
                }
                if (!$matches) {
                    return '';
                }
            } elseif ($assignments !== 'all') {
                return '';
            }
        }

        GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Rendering Gantry module '{$module['id']}'", 'info');

        /** @var Theme $theme */
        $theme = $this->container['theme'];

        if (isset($attribs['ajax']) && is_array($attribs['ajax'])) {
            $attribs['style'] = 'none';
        }

        $html = trim($theme->render('@nucleus/partials/module.html.twig', $attribs + ['inContent' => true, 'segment' => $module]));

        return $html;
    }

    public function displayModules($position, $attribs = [])
    {
        $html = '';
        foreach ($this->getModules($position) as $module) {
            $html .= $this->displayModule($module, $attribs + ['position' => $position]);
        }

        return $html;
    }


    public function displaySystemMessages($params = [])
    {
        return Gantry::instance()['theme']->compile(
            '{% for message in grav.messages.fetch %}<div class="alert-{{ message.scope|e }} alert">{{ message.message|raw }}</div>{% endfor %}'
        );
    }

    protected function getModule($id)
    {
        list($position, $module) = explode('/', $id, 2);

        return (new Module($module, $position))->toArray();
    }

    /**
     * Get preview url for individual theme.
     *
     * @param string $theme
     * @return null
     */
    public function getThemePreviewUrl($theme)
    {
        return null;
    }

    /**
     * Get administrator url for individual theme.
     *
     * @param string $theme
     * @return string|null
     */
    public function getThemeAdminUrl($theme)
    {
        $grav = Grav::instance();
        $base = $grav['gantry5_plugin']->base;

        return "{$base}/themes/{$theme}";
    }

    public function settings()
    {
        $grav = Grav::instance();
        return $grav['base_url_relative'] . $grav['admin']->base . '/plugins/gantry5';
    }

    public function truncate($text, $length, $html = false)
    {
        if ($html) {
            return $length ? Utils::truncateHtml($text, $length) : $text;
        } else {
            $text = strip_tags($text);
            return $length ? Utils::truncate($text, $length) : $text;
        }
    }

    /**
     * @param array|string $dependencies
     * @return bool|null
     * @since 5.4.3
     */
    public function checkDependencies($dependencies)
    {
        if (!parent::checkDependencies($dependencies)) {
            return false;
        }

        if (isset($dependencies['platform'][$this->name])) {
            $platform = $dependencies['platform'][$this->name];
            if (isset($platform['plugin']) && is_array($platform['plugin'])) {
                $plugins = Grav::instance()['plugins'];
                $list = $plugins->all();
                foreach ($platform['plugin'] as $name => $condition) {
                    $exists = isset($list[$name]);

                    if ($exists !== (bool) $condition) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
