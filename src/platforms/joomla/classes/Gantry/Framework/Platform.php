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

use Gantry\Admin\ThemeList;
use Gantry\Component\Config\Config;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Base\Platform as BasePlatform;
use Gantry\Joomla\Category\CategoryFinder;
use Gantry\Joomla\Content\Content;
use Gantry\Joomla\Content\ContentFinder;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use RocketTheme\Toolbox\DI\Container;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    /** @var bool */
    public $no_base_layout = false;

    /** @var string */
    public $module_wrapper = '%s';

    /** @var string */
    public $component_wrapper = '%s';

    /** @var HtmlDocument|null */
    public $document;

    /** @var string */
    protected $name = 'joomla';

    /** @var array */
    protected $features = ['modules' => true];

    /** @var string */
    protected $settings_key = 'return';

    /** @var array|null */
    protected $modules;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return JVERSION;
    }

    /**
     * @param string $html
     */
    public function setModuleWrapper($html): void
    {
        $this->module_wrapper = $html;
    }

    /**
     * @param string $html
     */
    public function setComponentWrapper($html): void
    {
        $this->component_wrapper = $html;
    }

    /**
     * @return Platform
     * @throws \RuntimeException
     */
    public function init(): Platform
    {
        // Support linked sample data.
        $theme = $this->container['theme.name'] ?? null;

        if ($theme && is_dir(JPATH_ROOT . "/media/gantry5/themes/{$theme}/media-shared")) {
            $custom = JPATH_ROOT . "/media/gantry5/themes/{$theme}/custom";

            if (!is_dir($custom)) {
                // First run -- copy configuration into a single location.
                $shared = JPATH_ROOT . "/media/gantry5/themes/{$theme}/template-shared";
                $demo = JPATH_ROOT . "/media/gantry5/themes/{$theme}/template-demo";

                try {
                    Folder::create($custom);
                } catch (\Exception $e) {
                    throw new \RuntimeException(sprintf("Failed to create folder '%s'.", $custom), 500, $e);
                }

                if (is_dir("{$shared}/custom/config")) {
                    Folder::copy("{$shared}/custom/config", "{$custom}/config");
                }

                if (is_dir("{$demo}/custom/config")) {
                    Folder::copy("{$demo}/custom/config", "{$custom}/config");
                }
            }

            array_unshift($this->items['streams']['gantry-theme']['prefixes'][''], "media/gantry5/themes/{$theme}/template-shared");
            array_unshift($this->items['streams']['gantry-theme']['prefixes'][''], "media/gantry5/themes/{$theme}/template-demo");
            array_unshift($this->items['streams']['gantry-theme']['prefixes'][''], "media/gantry5/themes/{$theme}/custom");
        }

        // Add media stream locations
        $this->items['streams']['media-vendor'] = [
            'type' => 'ReadOnlyStream',
            'prefixes' => ['' => ['media/vendor']]
        ];

        if (!isset($this->items['streams']['media-templates'])) {
            $this->items['streams']['media-templates'] = [
                'prefixes' => ['' => []]
            ];
        }

        return parent::init();
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getCachePath(): string
    {
        $path = Factory::getApplication()->get('cache_path', JPATH_CACHE);

        if (!is_dir($path)) {
            throw new \RuntimeException('Joomla cache path does not exist!');
        }

        return $path . '/gantry5';
    }

    /**
     * @return array
     */
    public function getThemesPaths(): array
    {
        return ['' => ['templates']];
    }

    /**
     * @return array
     */
    public function getMediaPaths(): array
    {
        $paths = ['images'];

        // Support linked sample data.
        $theme = $this->container['theme.name'] ?? null;

        if ($theme && \is_dir(JPATH_ROOT . "/media/gantry5/themes/{$theme}/media-shared")) {
            \array_unshift($paths, "media/gantry5/themes/{$theme}/media-shared");
            \array_unshift($paths, "media/gantry5/themes/{$theme}/media-demo");
        }

        /** @var Config $global */
        $global = $this->container['global'];

        if ($global->get('use_media_folder', false)) {
            $paths[] = 'gantry-theme://images';
        } else {
            \array_unshift($paths, 'gantry-theme://images');
        }

        return ['' => $paths];
    }

    /**
     * @return array
     */
    public function getEnginePaths($name = 'nucleus'): array
    {
        $theme = $this->container['theme.name'] ?? null;
        $path  = $this->container['theme.path'] ?? null;

        if ($theme && $path) {
            $cachePath = $this->getCachePath() . "/{$theme}/compiled/yaml";
            $path      = $this->container['theme.path'];
            $details   = new ThemeDetails($theme, $path, $cachePath);
            $name      = $details->get('configuration.gantry.engine', $name);
        }

        return parent::getEnginePaths($name);
    }

    /**
     * @return array
     */
    public function getEnginesPaths(): array
    {
        if ($this->container->isDev()) {
            // Development environment.
            return ['' => ["media/gantry5/engines/{$this->name}", 'media/gantry5/engines/common']];
        }

        return ['' => ['media/gantry5/engines']];
    }

    /**
     * @return array
     */
    public function getAssetsPaths(): array
    {
        if ($this->container->isDev()) {
            // Development environment.
            return ['' => ['gantry-theme://', "media/gantry5/assets", 'media/gantry5/assets/common']];
        }

        return ['' => ['gantry-theme://', 'media/gantry5/assets']];
    }

    /**
     * Get preview url for individual theme.
     *
     * @param string $theme
     * @return string
     */
    public function getThemePreviewUrl($theme)
    {
        return (string)(int) $theme === (string) $theme ? Uri::root(false) . 'index.php?templateStyle=' . $theme : null;
    }

    /**
     * Get administrator url for individual theme.
     *
     * @param string $theme
     * @return string
     */
    public function getThemeAdminUrl($theme)
    {
        $token = Factory::getApplication()->getFormToken();

        return Route::_("index.php?option=com_gantry5&view=configurations/default/layout&theme={$theme}&{$token}=1", false);
    }

    /**
     * @param string $text
     * @return string
     */
    public function filter($text)
    {
        PluginHelper::importPlugin('content');

        return HTMLHelper::_('content.prepare', $text, '', 'mod_custom.content');
    }

    /**
     * @param string $position
     * @param ?bool $withContentOnly
     * @return int
     */
    public function countModules($position, $withContentOnly = false): int
    {
        $doc = Factory::getApplication()->getDocument();

        return ($doc instanceof HtmlDocument) ? $doc->countModules($position, $withContentOnly) : 0;
    }

    /**
     * @param string $position
     * @return array
     */
    public function getModules($position): array
    {
        // TODO:
        return [];
    }

    /**
     * @param string|object $id
     * @param ?array $attribs
     * @return string
     */
    public function displayModule($id, $attribs = []): string
    {
        $app = Factory::getApplication();

        $module = \is_object($id) ? $id : $this->getModule($id);

        // Make sure that module really exists.
        if (!\is_object($module)) {
            return '';
        }

        if (empty($module->contentRendered)) {
            $doc = $app->getDocument();

            if (!$doc instanceof HtmlDocument) {
                return '';
            }

            $renderer = $doc->loadRenderer('module');

            $html = \trim($renderer->render($module, $attribs));
        } else {
            $html = \trim($module->content);
        }

        // Add frontend editing feature as it has only been defined for module positions.
        $user = $app->getIdentity();

        $frontEditing = $app->isClient('site') && $app->get('frontediting', 1 && $user && !$user->guest);
        $menusEditing = ($app->get('frontediting', 1) == 2) && $user && $user->authorise('core.edit', 'com_menus');

        $isGantry = \strpos($module->module, 'gantry5') !== false;

        if (
            !$isGantry
            && $frontEditing
            && $html
            && $user
            && $user->authorise('module.edit.frontend', 'com_modules.module.' . $module->id)
        ) {
            $displayData = [
                'moduleHtml'   => &$html,
                'module'       => $module,
                'position'     => $attribs['position'] ?? $module->position,
                'menusediting' => $menusEditing
            ];

            LayoutHelper::render('joomla.edit.frontediting_modules', $displayData);
        }

        if ($html && !$isGantry) {
            /** @var Theme $theme */
            $theme = $this->container['theme'];
            $theme->joomla(true);

            return \sprintf($this->module_wrapper, $html);
        }

        return $html;
    }

    /**
     * @param string $position
     * @param ?array $attribs
     * @return string
     */
    public function displayModules($position, $attribs = []): string
    {
        $doc = Factory::getApplication()->getDocument();

        if (!$doc instanceof HtmlDocument) {
            return '';
        }

        $html = '';

        foreach (ModuleHelper::getModules($position) as $module) {
            $html .= $this->displayModule($module, $attribs);
        }

        return $html;
    }

    /**
     * @param ?array $params
     * @return string
     */
    public function displaySystemMessages($params = []): string
    {
        // We cannot use DocumentHtml renderer here as it fires too early to display any messages.
        return '<jdoc:include type="message" />';
    }

    /**
     * @param string $content
     * @param ?array $params
     * @return string
     */
    public function displayContent($content, $params = []): string
    {
        $app = Factory::getApplication();
        $doc = $app->getDocument();

        if (!$doc instanceof HtmlDocument) {
            return $content;
        }

        $renderer = $doc->loadRenderer('component');
        $html     = \trim($renderer->render(null, $params, $content ?: $doc->getBuffer('component')));
        $isGantry = \strpos($app->getInput()->getCmd('option'), 'gantry5') !== false;

        if ($html && !$isGantry) {
            /** @var Theme $theme */
            $theme = $this->container['theme'];
            $theme->joomla(true);

            return \sprintf($this->component_wrapper, $html);
        }

        return $html;
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function getModule($id)
    {
        $modules = $this->getModuleList();

        return $id && isset($modules[$id]) ? $modules[$id] : null;
    }

    /**
     * @return array|null
     */
    protected function getModuleList()
    {
        if ($this->modules === null) {
            $modules = ModuleHelper::getModuleList();

            $this->modules = [];

            foreach ($modules as $module) {
                $this->modules[$module->id] = $module;
            }
        }
        return $this->modules;
    }

    /**
     * @return array|false|null
     */
    public function listModules()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();

        $query->select(
            [
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.position'),
                $db->quoteName('a.module'),
                $db->quoteName('a.published', 'enabled'),
                $db->quoteName('ag.title', 'access')
            ]
        )
            ->from($db->quoteName('#__modules', 'a'))
            ->join(
                'LEFT',
                $db->quoteName('#__viewlevels', 'ag'),
                $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access')
            )
            ->where(
                [
                    $db->quoteName('a.published') . ' >= 0',
                    $db->quoteName('a.client_id') . ' = 0'
                ]
            )
            ->order('a.position, a.module, a.ordering');

        try {
            $result = $db->setQuery($query)->loadObjectList();
        } catch (\RuntimeException $e) {
            return false;
        }

        return $result;
    }

    /**
     * @param string $name
     * @param ?string $content
     * @param ?string|int|null $width
     * @param ?string|int|null $height
     * @return string|null
     */
    public function getEditor($name, $content = '', $width = null, $height = null)
    {
        $app    = Factory::getApplication();
        $editor = Editor::getInstance($app->get('editor'));

        if (!$height) {
            $height = 250;
        }

        return $editor->display($name, $content, $width, $height, 50, 8, false, null, null, null, ['html_height' => $height]);
    }

    /**
     * @return array
     */
    public function errorHandlerPaths(): array
    {
        return ['|gantry5|'];
    }

    /**
     * @return string
     */
    public function settings(): string
    {
        if (!$this->authorize('platform.settings.manage')) {
            return '';
        }

        return Route::_('index.php?option=com_config&view=component&component=com_gantry5', false) ?: '';
    }

    /**
     * @return string
     */
    public function update(): string
    {
        return Route::_('index.php?option=com_installer&view=update', false) ?: '';
    }

    /**
     * @return array
     */
    public function updates(): array
    {
        if (!$this->authorize('updates.manage')) {
            return [];
        }

        $updateInformation = [
            'installed' => \GANTRY5_VERSION,
            'latest'    => null,
            'object'    => null,
            'hasUpdate' => false,
            'current'   => GANTRY5_VERSION, // This is deprecated please use 'installed' or JVERSION directly
        ];

        $styles = ThemeList::getThemes();

        $extension_ids = \array_unique(\array_map(
            function ($item) {
                return (int) $item->extension_id;
            },
            $styles
        ));

        $extension_ids = $extension_ids ? implode(',', $extension_ids) : '-1';

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();

        $query->select('*')
            ->from($db->quoteName('#__updates'))
            ->where("element='pkg_gantry5' OR extension_id IN ($extension_ids)");

        $updateObject = $db->setQuery($query)->loadObjectList();

        if (empty($updateObject)) {
            // We have not found any update in the database - we seem to be running the latest version.
            $updateInformation['latest'] = GANTRY5_VERSION;

            return $updateInformation;
        }

        foreach ($updateObject as $update) {
            if ($update->element === 'pkg_gantry5') {
                // Rename Gantry 5 package.
                $update->name = 'Gantry';

                // Ignore git and CI installs and if the Gantry version is the same or higher than in the updates.
                if (
                    \version_compare(GANTRY5_VERSION, 0) < 0
                    || \version_compare($update->version, GANTRY5_VERSION) <= 0
                ) {
                    continue;
                }

                $updateInformation['latest'] = $update->version;
            } else {
                // Check if templates need to be updated.
                $version = $styles[$update->element]?->get('details.version');

                if (
                    \version_compare($version, 0) < 0
                    || \version_compare($update->version, $version) <= 0
                ) {
                    continue;
                }
            }

            $updateInformation['hasUpdate'] = true;
        }

        return $updateInformation;
    }

    /**
     * @return mixed|null
     */
    public function factory()
    {
        $args   = \func_get_args();
        $method = [Factory::class, 'get' . \ucfirst((string) \array_shift($args))];

        return \method_exists($method[0], $method[1])
            ? \call_user_func_array($method, $args)
            : null;
    }

    /**
     * @return mixed|null
     * @deprecated   5.6 will be removed in 5.7
     *               Will be removed without replacement
     */
    public function instance()
    {
        @trigger_error(\sprintf('Use containers instead in %s.', __METHOD__), E_USER_DEPRECATED);

        $args  = \func_get_args();
        $class = \ucfirst((string) \array_shift($args));

        if (!$class) {
            return null;
        }

        $method = [$class, 'getInstance'];

        return \method_exists($method[0], $method[1])
            ? \call_user_func_array($method, $args)
            : null;
    }

    /**
     * @return string
     */
    public function route()
    {
        return \call_user_func_array([Route::class, '_'], \func_get_args()) ?: '';
    }

    /**
     * @param string $layoutFile
     * @param ?mixed $displayData
     * @param ?string $basePath
     * @param ?mixed $options
     * @return string
     */
    public function layout($layoutFile, $displayData = null, $basePath = '', $options = null): string
    {
        return LayoutHelper::render($layoutFile, $displayData, $basePath, $options);
    }

    /**
     * @return string
     */
    public function html()
    {
        $args = \func_get_args();

        if (isset($args[0]) && \method_exists(HTMLHelper::class, $args[0])) {
            return \call_user_func_array([HTMLHelper::class, \array_shift($args)], $args);
        }

        return \call_user_func_array([HTMLHelper::class, '_'], $args);
    }

    /**
     * @param int|array $keys
     * @return Object
     */
    public function article($keys)
    {
        return Content::getInstance($keys);
    }

    /**
     * @param string $domain
     * @param ?array|null $options
     * @return CategoryFinder|ContentFinder|null
     */
    public function finder($domain, $options = null)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        $options = (array) $options;
        switch ($domain) {
            case 'article':
            case 'articles':
            case 'content':
                $finder = new ContentFinder($options);

                return $app->isClient('site') ? $finder->authorised() : $finder;
            case 'category':
            case 'categories':
                $finder = (new CategoryFinder($options))->extension('content');

                return $app->isClient('site') ? $finder->authorised() : $finder;
        }

        return null;
    }

    /**
     * @param string $text
     * @param int $length
     * @param ?bool $html
     * @return string
     */
    public function truncate($text, $length, $html = false)
    {
        return HTMLHelper::_('string.truncate', $text, $length, true, $html);
    }

    /**
     * @param $action
     * @param ?int|string|null $id
     * @return bool
     * @throws \RuntimeException
     */
    public function authorize($action, $id = null)
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user) {
            return false;
        }

        switch ($action) {
            case 'platform.settings.manage':
                return $user->authorise('core.admin', 'com_templates') || $user->authorise('core.admin', 'com_gantry5');
            case 'menu.manage':
                /** @var Menu $menus */
                $menus = $this->container['menu'];

                if (null !== $id) {
                    $menu = $menus->instance(['menu' => $id, 'admin' => true]);

                    return $user->authorise('core.manage', 'com_menus.menu.' . $menu->id);
                }

                if ($user->authorise('core.manage', 'com_menus')) {
                    return true;
                }

                $menus = $menus->getMenuIds();

                foreach ($menus as $menuId) {
                    if ($user->authorise('core.manage', 'com_menus.menu.' . $menuId)) {
                        return true;
                    }
                }

                return false;
            case 'menu.edit':
                if ($id) {
                    /** @var Menu $menus */
                    $menus = $this->container['menu'];
                    $menu  = $menus->instance(['menu' => $id, 'admin' => true]);

                    if (!$user->authorise('core.edit', 'com_menus.menu.' . $menu->id)) {
                        return false;
                    }

                    $db = Factory::getContainer()->get(DatabaseInterface::class);

                    $userId = $user->id;
// TODO: fix query with bind
                    // Verify that no items are checked out.
                    $query = $db->createQuery()
                        ->select('id')
                        ->from($db->quoteName('#__menu'))
                        ->where('id=' . $db->quote($menu->id))
                        ->where('checked_out !=' . (int) $userId)
                        ->where('checked_out IS NOT null');
                    $db->setQuery($query);

                    if ($db->loadRowList()) {
                        return false;
                    }
// TODO: fix query with bind
                    // Verify that no module for this menu are checked out.
                    $query->clear()
                        ->select('id')
                        ->from($db->quoteName('#__modules'))
                        ->where('module=' . $db->quote('mod_menu'))
                        ->where('params LIKE ' . $db->quote('%"menutype":' . json_encode($id) . '%'))
                        ->where('checked_out !=' . (int) $userId)
                        ->where('checked_out IS NOT null');
                    $db->setQuery($query);

                    return !$db->loadRowList();
                }

                return $user->authorise('core.edit', 'com_menus');
            case 'updates.manage':
                return $user->authorise('core.manage', 'com_installer');
            case 'outline.create':
                return $user->authorise('core.create', 'com_templates');
            case 'outline.delete':
                return $user->authorise('core.delete', 'com_templates');
            case 'outline.rename':
                return $user->authorise('core.edit', 'com_templates');
            case 'outline.assign':
                return $user->authorise('core.edit.state', 'com_templates')
                    && $user->authorise('core.edit', 'com_menu');
            case 'outline.edit':
                return true;
        }

        return true;
    }
}
