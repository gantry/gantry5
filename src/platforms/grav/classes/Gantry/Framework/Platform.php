<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Position\Module;
use Gantry\Component\Position\Position;
use Gantry\Debugger;
use Gantry\Framework\Base\Platform as BasePlatform;
use Grav\Common\Grav;
use Grav\Common\Plugins;
use Grav\Common\User\Interfaces\UserInterface;
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
    /** @var string */
    protected $name = 'grav';
    /** @var array */
    protected $features = ['fontawesome' => false];

    /**
     * Platform constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);

        // Initialize custom streams for Grav.
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
    public function getVersion()
    {
        return Grav::instance()->getVersion();
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

    /**
     * @return array
     */
    public function getMediaPaths()
    {
        /** @var Config $global */
        $global = $this->container['global'];

        $paths = ['image://'];

        if ($global->get('use_media_folder', false)) {
            $paths[] = 'gantry-theme://images';
        } else {
            array_unshift($paths, 'gantry-theme://images');
        }

        return ['' => $paths];
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
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

    /**
     * @param string $position
     * @return int
     */
    public function countModules($position)
    {
        return count($this->getModules($position));
    }

    /**
     * @param string $position
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
                $matches = (new Assignments())->matches(['test' => $assignments]);
                if (\GANTRY_DEBUGGER) {
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

        if (\GANTRY_DEBUGGER) {
            Debugger::addMessage("Rendering Gantry module '{$module['id']}'", 'info');
        }

        /** @var Theme $theme */
        $theme = $this->container['theme'];

        if (isset($attribs['ajax']) && is_array($attribs['ajax'])) {
            $attribs['style'] = 'none';
        }

        $html = trim($theme->render('@nucleus/partials/module.html.twig', $attribs + ['inContent' => true, 'segment' => $module]));

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
            $html .= $this->displayModule($module, $attribs + ['position' => $position]);
        }

        return $html;
    }

    /**
     * @param array $params
     * @return string
     */
    public function displaySystemMessages($params = [])
    {
        /** @var Theme $theme */
        $theme = Gantry::instance()['theme'];

        return $theme->compile(
            '{% for message in grav.messages.fetch %}<div class="alert-{{ message.scope|e }} alert">{{ message.message|raw }}</div>{% endfor %}'
        );
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
     * @param string $text
     * @return string
     */
    public function filter($text)
    {
        $shortcode = isset(Grav::instance()['shortcode']) ? Grav::instance()['shortcode'] : null;
        if ($shortcode && method_exists($shortcode, 'processShortcodes')) {
            return $shortcode->processShortcodes($text);
        }

        return $text;
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

    /**
     * @return string
     */
    public function settings()
    {
        if (!$this->authorize('platform.settings.manage')) {
            return '';
        }

        $grav = Grav::instance();

        return $grav['base_url_relative'] . $grav['admin']->base . '/plugins/gantry5';
    }

    /**
     * @param string $text
     * @param int $length
     * @param bool $html
     * @return string
     */
    public function truncate($text, $length, $html = false)
    {
        if ($html) {
            return $length ? Utils::truncateHtml($text, $length) : $text;
        }

        $text = strip_tags($text);

        return $length ? Utils::truncate($text, $length) : $text;
    }

    /**
     * @param string $action
     * @param int|string|null $id
     * @return bool
     */
    public function authorize($action, $id = null)
    {
        // TODO: hook everything into ACL
        static $actions = [
            'platform.settings.manage' => 'admin.plugins',
            'updates.manage' => null,
            'menu.manage' => null,
            'menu.edit' => null,
            'outline.create' => null,
            'outline.rename' => null,
            'outline.delete' => null,
            'outline.assign' => null
        ];

        if (isset($actions[$action])) {
            $action = $actions[$action];

            $grav = Grav::instance();
            if (isset($grav['admin'])) {
                /** @var UserInterface $user */
                $user = $grav['admin']->user;
            } else {
                /** @var UserInterface $user */
                $user = $grav['user'];
            }

            return $user->authorize($action) ?: false;
        }

        return true;
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
                /** @var Plugins $plugins */
                $plugins = Grav::instance()['plugins'];
                $list = $plugins::all();

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
