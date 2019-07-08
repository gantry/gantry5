<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Admin\ThemeList;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Platform as BasePlatform;
use Gantry\Joomla\Category\CategoryFinder;
use Gantry\Joomla\Content\Content;
use Gantry\Joomla\Content\ContentFinder;
use Gantry\Joomla\JoomlaFactory;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    public $no_base_layout = false;
    public $module_wrapper = '<div class="platform-content">%s</div>';
    public $component_wrapper = '<div class="platform-content row-fluid"><div class="span12">%s</div></div>';

    protected $name = 'joomla';
    protected $features = ['modules' => true];
    protected $settings_key = 'return';
    protected $modules;


    /**
     * @param string $html
     */
    public function setModuleWrapper($html)
    {
        $this->module_wrapper = $html;
    }

    /**
     * @param string $html
     */
    public function setComponentWrapper($html)
    {
        $this->component_wrapper = $html;
    }

    /**
     * @return BasePlatform
     * @throws \RuntimeException
     */
    public function init()
    {
        // Support linked sample data.
        $theme = isset($this->container['theme.name']) ? $this->container['theme.name'] : null;
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

        return parent::init();
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getCachePath()
    {
        $path = JoomlaFactory::getConfig()->get('cache_path', JPATH_SITE . '/cache');
        if (!is_dir($path)) {
            throw new \RuntimeException('Joomla cache path does not exist!');
        }

        return $path . '/gantry5';
    }

    /**
     * @return array
     */
    public function getThemesPaths()
    {
        return ['' => ['templates']];
    }

    /**
     * @return array
     */
    public function getMediaPaths()
    {
        $paths = ['images'];

        // Support linked sample data.
        $theme = isset($this->container['theme.name']) ? $this->container['theme.name'] : null;
        if ($theme && is_dir(JPATH_ROOT . "/media/gantry5/themes/{$theme}/media-shared")) {
            array_unshift($paths, "media/gantry5/themes/{$theme}/media-shared");
            array_unshift($paths, "media/gantry5/themes/{$theme}/media-demo");
        }

        if ($this->container['global']->get('use_media_folder', false)) {
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
        if (is_link(GANTRY5_ROOT . '/media/gantry5/engines')) {
            // Development environment.
            return ['' => ["media/gantry5/engines/{$this->name}", 'media/gantry5/engines/common']];
        }
        return ['' => ['media/gantry5/engines']];
    }

    /**
     * @return array
     */
    public function getAssetsPaths()
    {
        if (is_link(GANTRY5_ROOT . '/media/gantry5/assets')) {
            // Development environment.
            return ['' => ['gantry-theme://', "media/gantry5/assets/{$this->name}", 'media/gantry5/assets/common']];
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
        $session = JoomlaFactory::getSession();
        $token = $session::getFormToken();

        return Route::_("index.php?option=com_gantry5&view=configurations/default/styles&theme={$theme}&{$token}=1" , false);
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
     * @return int
     */
    public function countModules($position)
    {
        $document = JoomlaFactory::getDocument();

        return ($document instanceof HtmlDocument) ? $document->countModules($position) : 0;
    }

    /**
     * @param string $position
     * @return array
     */
    public function getModules($position)
    {
        // TODO:
        return [];
    }

    /**
     * @param string|object $id
     * @param array $attribs
     * @return string
     */
    public function displayModule($id, $attribs = [])
    {
        $document = JoomlaFactory::getDocument();
        if (!$document instanceof HtmlDocument) {
            return '';
        }

        $module = is_object($id) ? $id : $this->getModule($id);

        // Make sure that module really exists.
        if (!is_object($module)) {
            return '';
        }

        $isGantry = \strpos($module->module, 'gantry5') !== false;
        $content = isset($module->content) ? $module->content : null;

        $renderer = $document->loadRenderer('module');

        $html = trim($renderer->render($module, $attribs));

        // Add frontend editing feature as it has only been defined for module positions.
        $app = JoomlaFactory::getApplication();
        $user = JoomlaFactory::getUser();

        // FIXME: Joomla 4: check $app->get()
        $frontEditing = ($app->isClient('site') && $app->get('frontediting', 1) && !$user->guest);
        $menusEditing = ($app->get('frontediting', 1) == 2) && $user->authorise('core.edit', 'com_menus');

        if (!$isGantry && $frontEditing && $html && $user->authorise('module.edit.frontend', 'com_modules.module.' . $module->id)) {
            $displayData = [
                'moduleHtml' => &$html,
                'module' => $module,
                'position' => isset($attribs['position']) ? $attribs['position'] : $module->position,
                'menusediting' => $menusEditing
            ];
            LayoutHelper::render('joomla.edit.frontediting_modules', $displayData);
        }

        // Work around Joomla "issue" which corrupts content of custom html module (last checked J! 3.6.5).
        $module->content = $content;

        if ($html && !$isGantry) {
            /** @var Theme $theme */
            $theme = $this->container['theme'];
            $theme->joomla(true);
            return sprintf($this->module_wrapper, $html);
        }

        return $html;
    }

    /**
     * @param string $position
     * @param array $attribs
     * @return string
     */
    public function displayModules($position, $attribs = [])
    {
        $document = JoomlaFactory::getDocument();
        if (!$document instanceof HtmlDocument) {
            return '';
        }

        $html = '';
        foreach (ModuleHelper::getModules($position) as $module) {
            $html .= $this->displayModule($module, $attribs);
        }

        return $html;
    }

    /**
     * @param array $params
     * @return string
     */
    public function displaySystemMessages($params = [])
    {
        // We cannot use DocumentHtml renderer here as it fires too early to display any messages.
        return '<jdoc:include type="message" />';
    }

    /**
     * @param string $content
     * @param array $params
     * @return string
     */
    public function displayContent($content, $params = [])
    {
        $document = JoomlaFactory::getDocument();
        if (!$document instanceof HtmlDocument) {
            return $content;
        }

        $renderer = $document->loadRenderer('component');

        $html = trim($renderer->render(null, $params, $content ?: $document->getBuffer('component')));

        $isGantry = \strpos(JoomlaFactory::getApplication()->input->getCmd('option'), 'gantry5') !== false;

        if ($html && !$isGantry) {
            /** @var Theme $theme */
            $theme = $this->container['theme'];
            $theme->joomla(true);
            return sprintf($this->component_wrapper, $html);
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
    protected function &getModuleList()
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
        $db = JoomlaFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id, a.title, a.position, a.module, a.published AS enabled')
            ->from('#__modules AS a');

        // Join on the asset groups table.
        $query->select('ag.title AS access')
            ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access')
            ->where('a.published >= 0')
            ->where('a.client_id = 0')
            ->order('a.position, a.module, a.ordering');

        $db->setQuery($query);

        try {
            $result = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            return false;
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $content
     * @param string|int|null $width
     * @param string|int|null $height
     * @return string|null
     */
    public function getEditor($name, $content = '', $width = null, $height = null)
    {
        $config = JoomlaFactory::getConfig();
        $editor = Editor::getInstance($config->get('editor'));
        if (!$height) {
            $height = 250;
        }

        return $editor->display($name, $content, $width, $height, 50, 8, false, null, null, null, ['html_height' => $height]);
    }

    /**
     * @return array
     */
    public function errorHandlerPaths()
    {
        return ['|gantry5|'];
    }

    /**
     * @return string
     */
    public function settings()
    {
        if (!$this->authorize('platform.settings.manage')) {
            return '';
        }

        return Route::_('index.php?option=com_config&view=component&component=com_gantry5', false) ?: '';
    }

    /**
     * @return string
     */
    public function update()
    {
        return Route::_('index.php?option=com_installer&view=update', false) ?: '';
    }

    /**
     * @return array
     */
    public function updates()
    {
        if (!$this->authorize('updates.manage')) {
            return [];
        }

        $styles = ThemeList::getThemes();

        $extension_ids = array_unique(array_map(
            function($item) {
                return (int) $item->extension_id;
            },
            $styles));

        $extension_ids = $extension_ids ? implode(',', $extension_ids) : '-1';

        $db = JoomlaFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select('*')
            ->from('#__updates')
            ->where("element='pkg_gantry5' OR extension_id IN ($extension_ids)");

        $db->setQuery($query);

        $updates = $db->loadObjectList();

        $list = [];
        foreach ($updates as $update) {
            if ($update->element === 'pkg_gantry5') {
                // Rename Gantry 5 package.
                $update->name = 'Gantry';
                // Ignore git and CI installs and if the Gantry version is the same or higher than in the updates.
                if (version_compare(GANTRY5_VERSION, 0) < 0 || version_compare($update->version, GANTRY5_VERSION) <= 0) {
                    continue;
                }
            } else {
                // Check if templates need to be updated.
                $version = isset($styles[$update->element]) ? $styles[$update->element]->get('details.version') : null;
                if (version_compare($version, 0) < 0 || version_compare($update->version, $version) <= 0) {
                    continue;
                }
            }
            $list[] = $update->name . ' ' . $update->version;
        }

        return $list;
    }

    /**
     * @return mixed|null
     */
    public function factory()
    {
        $args = func_get_args();
        $method = [Factory::class, 'get'. ucfirst((string) array_shift($args))];
        return method_exists($method[0], $method[1]) ? \call_user_func_array($method, $args) : null;
    }

    /**
     * @return mixed|null
     */
    public function instance()
    {
        $args = func_get_args();
        $class = ucfirst((string) array_shift($args));
        if (!$class) {
            return null;
        }
        if (class_exists('J'. $class)) {
            $class = 'J'. $class;
        }
        $method = [$class, 'getInstance'];
        return method_exists($method[0], $method[1]) ? \call_user_func_array($method, $args) : null;
    }

    /**
     * @return string
     */
    public function route()
    {
        return \call_user_func_array([Route::class, '_'], func_get_args()) ?: '';
    }

    /**
     * @return string
     */
    public function html()
    {
        $args = func_get_args();
        if (isset($args[0]) && method_exists(HTMLHelper::class, $args[0])) {
            return \call_user_func_array([HTMLHelper::class, array_shift($args)], $args);
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
     * @param array|null $options
     * @return CategoryFinder|ContentFinder|null
     */
    public function finder($domain, $options = null)
    {
        $options = (array) $options;
        switch ($domain) {
            case 'article':
            case 'articles':
            case 'content':
                $finder = new ContentFinder($options);

                return JoomlaFactory::getApplication()->isClient('site') ? $finder->authorised() : $finder;
            case 'category':
            case 'categories':
                $finder = (new CategoryFinder($options))->extension('content');

                return JoomlaFactory::getApplication()->isClient('site') ? $finder->authorised() : $finder;
        }

        return null;
    }

    /**
     * @param string $text
     * @param int $length
     * @param bool $html
     * @return string
     */
    public function truncate($text, $length, $html = false)
    {
        return HTMLHelper::_('string.truncate', $text, $length, true, $html);
    }

    /**
     * @param $action
     * @param null $id
     * @return bool
     * @throws \RuntimeException
     */
    public function authorize($action, $id = null)
    {
        $user = JoomlaFactory::getUser();

        switch ($action) {
            case 'platform.settings.manage':
                return $user->authorise('core.admin', 'com_templates') || $user->authorise('core.admin', 'com_gantry5');
            case 'menu.manage':
                return $user->authorise('core.manage', 'com_menus') && $user->authorise('core.edit', 'com_menus');
            case 'menu.edit':
                if ($id) {
                    $db = JoomlaFactory::getDbo();
                    $userId = $user->id;

                    // Verify that no items are checked out.
                    $query = $db->getQuery(true)
                        ->select('id')
                        ->from('#__menu')
                        ->where('menutype=' . $db->quote($id))
                        ->where('checked_out !=' . (int) $userId)
                        ->where('checked_out !=0');
                    $db->setQuery($query);

                    if ($db->loadRowList()) {
                        return false;
                    }

                    // Verify that no module for this menu are checked out.
                    $query->clear()
                        ->select('id')
                        ->from('#__modules')
                        ->where('module=' . $db->quote('mod_menu'))
                        ->where('params LIKE ' . $db->quote('%"menutype":' . json_encode($id) . '%'))
                        ->where('checked_out !=' . (int) $userId)
                        ->where('checked_out !=0');
                    $db->setQuery($query);

                    if ($db->loadRowList()) {
                        return false;
                    }
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
                return $user->authorise('core.edit.state', 'com_templates') && $user->authorise('core.edit', 'com_menu');
            case 'outline.edit':
                return true;
        }

        return true;
    }
}
