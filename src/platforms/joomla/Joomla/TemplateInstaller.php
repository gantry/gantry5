<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Gantry;
use Gantry\Framework\Outlines;
use Gantry\Framework\Platform;
use Gantry\Framework\Services\ErrorServiceProvider;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class TemplateInstaller
{
    /**
     * Set to true if in Gantry.
     *
     * @var bool
     */
    public $initialized = false;
    protected $extension;
    protected $outlines;
    protected $script;
    protected $manifest;
    protected $actions = [];

    public function __construct($extension = null)
    {
        jimport('joomla.filesystem.folder');

        \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
        if ($extension instanceof \JInstallerAdapterTemplate) {
            $this->setInstaller($extension);
        } elseif ($extension) {
            $this->loadExtension($extension);
        }
    }

    public function setInstaller(\JInstallerAdapterTemplate $install)
    {
        // We need access to a protected variable $install->extension.
        $reflectionClass = new \ReflectionClass($install);
        $property = $reflectionClass->getProperty('extension');
        $property->setAccessible(true);
        $this->extension = $property->getValue($install);

        $this->manifest = new Manifest($this->extension->name, $install->getManifest());

        return $this;
    }

    public function loadExtension($id)
    {
        if ((string) intval($id) !== (string) $id) {
            $id = ['type' => 'template', 'element' => (string) $id, 'client_id' => 0];
        }
        $this->extension = \JTable::getInstance('extension');
        $this->extension->load($id);
    }

    public function getPath()
    {
        return JPATH_SITE . '/templates/' . $this->extension->name;
    }

    public function getStyleName($title)
    {
        return \JText::sprintf($title, \JText::_($this->extension->name));
    }

    public function getStyle($name = null)
    {
        if (is_numeric($name)) {
            $field = 'id';
        } else {
            $field = 'title';
            $name = $this->getStyleName($name);
        }

        $style = $this->createStyle();
        $style->load([
            'template' => $this->extension->element,
            'client_id' => $this->extension->client_id,
            $field => $name
        ]);

        return $style;
    }

    public function getDefaultStyle()
    {
        $style = \JTable::getInstance('Style', 'TemplatesTable');
        $style->load(['home' => 1, 'client_id' => 0]);

        return $style;
    }

    /**
     * Get list of available outlines.
     *
     * @param array $filter
     * @return array
     */
    public function getOutlines(array $filter = null)
    {
        if (!isset($this->outlines)) {
            $this->outlines = [];
            $path = $this->getPath();

            // If no outlines are given, try loading outlines.yaml file.
            $file = YamlFile::instance($path . '/install/outlines.yaml');

            if ($file->exists()) {
                // Load the list from the yaml file.
                $this->outlines = (array)$file->content();
                $file->free();
            } elseif (is_dir($path . '/install/outlines')) {
                // Build the list from the install folder.
                $folders = \JFolder::folders($path . '/install/outlines', '.', false, true);
                foreach ($folders as $folder) {
                    $this->outlines[basename($folder)] = [];
                }
            }
        }

        return is_array($filter) ? array_intersect_key($this->outlines, array_flip($filter)) : $this->outlines;
    }

    public function getOutline($name)
    {
        $list = $this->getOutlines([$name]);

        return reset($list);
    }

    /**
     * @param string $type
     * @return \JTableMenu
     */
    public function getMenu($type)
    {
        /** @var \JTableMenuType $table */
        $table = \JTable::getInstance('MenuType');
        $table->load(['menutype' => $type]);

        return $table;
    }

    public function installDefaults()
    {
        $installerScript = $this->getInstallerScript();

        if (method_exists($installerScript, 'installDefaults')) {
            $installerScript->installDefaults($this);
        } else {
            $this->createDefaults();
        }
    }

    public function installSampleData()
    {
        $installerScript = $this->getInstallerScript();

        if (method_exists($installerScript, 'installSampleData')) {
            $installerScript->installSampleData($this);
        } else {
            $this->createSampleData();
        }
    }

    public function createDefaults()
    {
        $this->createOutlines();
    }

    public function createSampleData()
    {
        $this->updateStyle('JLIB_INSTALLER_DEFAULT_STYLE', [], 1);
        $this->installMenus();
    }

    public function render($template, $context = [])
    {
        try {
            $loader = new \Twig_Loader_Filesystem();
            $loader->setPaths([$this->getPath() . '/install/templates']);

            $params = [
                'cache' => null,
                'debug' => false,
                'autoescape' => 'html'
            ];

            $twig = new \Twig_Environment($loader, $params);

            $name = $this->extension->name;
            $token = \JSession::getFormToken();
            $manifest = $this->getManifest();
            $context += [
                'name' => \JText::_($name),
                'description' => \JText::_((string) $manifest->get('description')),
                'version' => (string) $manifest->get('version'),
                'date' => (string) $manifest->get('creationDate'),
                'author' => [
                    'name' => (string) $manifest->get('author'),
                    'email' => (string) $manifest->get('authorEmail'),
                    'url' => (string) $manifest->get('authorUrl')
                ],
                'copyright' => (string) $manifest->get('copyright'),
                'license' => (string) $manifest->get('license'),
                'install_url' => \JRoute::_("index.php?option=com_gantry5&view=install&theme={$name}&{$token}=1", false),
                'edit_url' => \JRoute::_("index.php?option=com_gantry5&view=configurations/default/styles&theme={$name}&{$token}=1", false),
                'actions' => $this->actions
            ];

            return $twig->render($template, $context);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function createStyle()
    {
        $style = \JTable::getInstance('Style', 'TemplatesTable');
        $style->reset();
        $style->template = $this->extension->element;
        $style->client_id = $this->extension->client_id;

        return $style;
    }

    public function addStyle($title, array $configuration = [], $home = 0)
    {
        // Make sure language debug is turned off.
        $lang = \JFactory::getLanguage();
        $debug = $lang->setDebug(false);

        // Translate title.
        $title = $this->getStyleName($title);

        // Turn language debug back on.
        $lang->setDebug($debug);

        $data = [
            'home' => (int) $home,
            'title' => $title,
            'params' => json_encode($configuration),
        ];

        $style = $this->createStyle();
        $style->save($data);

        if ($home) {
            $this->actions[] = ['action' => 'default_style_assigned', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_DEFAULT_STYLE_ASSIGNED', $title)];
        }

        return $style;
    }

    public function updateStyle($name, array $configuration, $home = null)
    {
        $style = $this->getStyle($name);

        if ($style->id) {
            $home = ($home !== null ? $home : $style->home);
            $params = (array) json_decode($style->params, true);

            $data = [
                'params' => json_encode($configuration + $params),
                'home' => $home
            ];

            if ($home && !$style->home) {
                $this->actions[] = ['action' => 'default_style_assigned', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_DEFAULT_STYLE_ASSIGNED', $style->title)];
            }

            $style->save($data);
        }

        return $style;
    }

    public function assignHomeStyle($style)
    {
        // Update the mapping for menu items that this style IS assigned to.
        $db = \JFactory::getDbo();

        $query = $db->getQuery(true)
            ->update('#__menu')
            ->set('template_style_id=' . (int) $style->id)
            ->where('home=1')
            ->where('client_id=0');
        $db->setQuery($query);
        $db->execute();

        if ($db->getAffectedRows()) {
            $this->actions[] = ['action' => 'home_style_assigned', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_HOME_STYLE_ASSIGNED', $style->title)];
        }
    }

    /**
     * Set available outlines.
     *
     * @param array $outlines If parameter isn't provided, outlines list get reloaded from the disk.
     * @return $this
     */
    public function setOutlines(array $outlines = null)
    {
        $this->outlines = $outlines;

        return $this;
    }

    /**
     * @param array $filter
     */
    public function createOutlines(array $filter = null)
    {
        $outlines = $this->getOutlines($filter);

        foreach ($outlines as $folder => $params) {
            $this->createOutline($folder, $params);
        }
    }

    /**
     * @param string $folder
     * @param array $params
     * @return string|bool
     */
    public function createOutline($folder, array $params = [])
    {
        if (!$folder) {
            throw new \RuntimeException('Cannot create outline without folder name');
        }

        $this->initialize();

        $created = false;

        $params += [
            'preset' => null,
            'title' => null
        ];

        $title = $params['title'] ?: ucwords(trim(strtr($folder, ['_' => ' '])));
        $preset = $params['preset'];

        if ($folder[0] !== '_') {
            $title = $this->getStyleName("%s - {$title}");
            $style = $this->getStyle($title);

            if (!$style->id) {
                // Only add style if it doesn't exist.
                $style = $this->addStyle($title, []);
                $created = true;
            }

            $id = $style->id;

        } else {
            $id = $folder;
        }

        $target = $folder !== 'default' ? $id : $folder;

        // Copy configuration for the new layout.
        if (($this->copyCustom($folder, $target) || $created) && isset($style)) {
            // Update layout and save it.
            $layout = Layout::load($target, $preset);
            $layout->save()->saveIndex();

            if ($id !== $target) {
                // Default outline: Inherit everything from the base.
                $layout->inheritAll()->name = $id;
                $layout->save()->saveIndex();

                $this->actions[] = ['action' => 'base_outline_created', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_BASE_OUTLINE_CREATED', $title)];
            }

            if ($created) {
                $this->actions[] = ['action' => 'outline_created', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_OUTLINE_CREATED', $title)];
            } else {
                $this->actions[] = ['action' => 'outline_updated', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_OUTLINE_UPDATED', $title)];
            }

            // Update preset in Joomla table.
            $this->updateStyle($title, ['preset' => $layout['preset']['name']]);
        }

        return $id;
    }

    /**
     * @param  array $item       [menutype, title, alias, link, template_style_id, params]
     * @param  int   $parent_id  Parent menu id.
     * @param  bool  $load       True if updating existing items.
     * @return int
     * @throws \Exception
     */
    public function addMenuItem(array $item, $parent_id = 1, $load = false)
    {
        $component_id = $this->getComponent();

        $table = \JTable::getInstance('menu');
        $date = new \JDate();
        $update = false;

        // Defaults for the item.
        $item += [
            'menutype' => 'mainmenu',
            'title' => 'Home',
            'alias' => 'gantry5',
            'note' => '',
            'link' => 'index.php?option=com_gantry5&view=custom',
            'type' => 'component',
            'published' => 1,
            'parent_id' => $parent_id,
            'component_id' => $component_id,
            'checked_out' => 0,
            'checked_out_time' => $date->toSql(),
            'browserNav' => 0,
            'access' => 1,
            'img' => '',
            'template_style_id' => 0,
            'params' => '{}',
            'home' => 0,
            'language' => '*',
            'client_id' => 0
        ];

        if (in_array($item['type'], ['separator', 'heading'])) {
            $item['link'] = '';
        }

        if ($item['type'] !== 'component') {
            $item['component_id'] = 0;
        }

        if ($load) {
            $update = $table->load([
                'menutype' => $item['menutype'],
                'alias' => $item['alias'],
                'parent_id' => $item['parent_id']
            ]);
        }

        $table->setLocation($parent_id, 'last-child');

        if (!$table->bind($item) || !$table->check() || !$table->store()) {
            throw new \Exception($table->getError());
        }

        /** @var \JCache|\JCacheController $cache */
        $cache = \JFactory::getCache();
        $cache->clean('mod_menu');

        $menu = \JTable::getInstance('menuType');
        $menu->load(['menutype' => $item['menutype']]);

        if (!isset($this->actions["menu_{$item['menutype']}_created"])) {
            $postfix = $item['home'] ? '_HOME' : '';
            if ($update) {
                $this->actions[] = ['action' => 'menu_item_updated', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_MENU_ITEM_UPDATED' . $postfix, $table->title, $table->path, $menu->title)];
            } else {
                $this->actions[] = ['action' => 'menu_item_created', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_MENU_ITEM_CREATED' . $postfix, $table->title, $table->path, $menu->title)];
            }
        } elseif ($item['home']) {
            $this->actions[] = ['action' => 'menu_item_updated', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_MENU_ITEM_HOME', $table->title, $table->path, $menu->title)];
        }

        return $table->id;
    }

    public function installMenus(array $menus = null, $parent = 1)
    {
        if ($menus === null) {
            $path = $this->getPath();

            $file = YamlFile::instance($path . '/install/menus.yaml');
            $menus = (array) $file->content();
            $file->free();
        }

        foreach ($menus as $menutype => $menu) {
            $title = !empty($menu['title']) ? $menu['title'] : ucfirst($menutype);
            $description = !empty($menu['description']) ? $menu['description'] : '';

            $exists = $this->getMenu($menutype)->id;

            // If $parent = 0, do dry run.
            if ((int) $parent && !$exists) {
                $this->deleteMenu($menutype, true);
                $this->createMenu($menutype, $title, $description);
            }

            if (!empty($menu['items'])) {
                $this->addMenuItems($menutype, $menu['items'], (int) $parent);
            }
        }
    }

    /**
     * @param string $type
     * @param string $title
     * @param string $description
     * @throws \Exception
     */
    public function createMenu($type, $title, $description)
    {
        /** @var \JTableMenuType $table */
        $table = \JTable::getInstance('MenuType');
        $data  = array(
            'menutype'    => $type,
            'title'       => $title,
            'description' => $description
        );

        if (!$table->bind($data) || !$table->check()) {
            // Menu already exists, do nothing
            return;
        }

        if (!$table->store()) {
            throw new \Exception($table->getError());
        }

        $this->actions["menu_{$type}_created"] = ['action' => 'menu_created', 'text' => \JText::sprintf('GANTRY5_INSTALLER_ACTION_MENU_CREATED', $title)];
    }

    /**
     * @param string $type
     * @param bool $force
     */
    public function deleteMenu($type, $force = false)
    {
        if ($force) {
            $this->unsetHome($type);
        }

        $table = \JTable::getInstance('MenuType');
        $table->load(array('menutype' => $type));

        if ($table->id) {
            $success = $table->delete();

            if (!$success) {
                \JFactory::getApplication()->enqueueMessage($table->getError(), 'error');
            } else {
                $this->actions["menu_{$type}_deleted"] = ['action' => 'menu_delete', 'text' => \JText::_('GANTRY5_INSTALLER_ACTION_MENU_DELETED', $table->title)];
            }
        }

        /** @var \JCache|\JCacheController $cache */
        $cache = \JFactory::getCache();
        $cache->clean('mod_menu');
    }

    public function unsetHome($type)
    {
        // Update the mapping for menu items that this style IS assigned to.
        $db = \JFactory::getDbo();

        $query = $db->getQuery(true)
            ->update('#__menu')
            ->set('home=0')
            ->where('menutype=' . $db->quote($type))
            ->where('client_id=0');
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * @deprecated 5.3.2
     */
    public function creanup()
    {
        $this->initialize();
        $this->finalize();
    }

    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $name = $this->extension->name;
        $path = $this->getPath();

        // Remove compiled CSS files if they exist.
        $cssPath = $path . '/custom/css-compiled';
        if (is_dir($cssPath)) {
            \JFolder::delete($cssPath);
        } elseif (is_file($cssPath)) {
            \JFile::delete($cssPath);
        }

        // Remove wrongly named file if it exists.
        $md5path = $path . '/MD5SUM';
        if (is_file($md5path)) {
            \JFile::delete($md5path);
        }

        // Restart Gantry and initialize it.
        $gantry = Gantry::restart();
        $gantry['theme.name'] = $name;
        $gantry['streams']->register();

        // Only add error service if debug mode has been enabled.
        if ($gantry->debug()) {
            $gantry->register(new ErrorServiceProvider);
        }

        /** @var Platform $patform */
        $patform = $gantry['platform'];

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        // Initialize theme stream.
        $details = new ThemeDetails($name);
        $locator->addPath('gantry-theme', '', $details->getPaths(), false, true);

        // Initialize theme cache stream and clear theme cache.
        $cachePath = $patform->getCachePath() . '/' . $name;
        if (is_dir($cachePath)) {
            Folder::delete($cachePath);
        }
        Folder::create($cachePath);
        $locator->addPath('gantry-cache', 'theme', [$cachePath], true, true);

        CompiledYamlFile::$defaultCachePath = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
        CompiledYamlFile::$defaultCaching = $gantry['global']->get('compile_yaml', 1);

        $this->initialized = true;
    }

    public function finalize()
    {
        $this->initialize();

        $gantry = Gantry::instance();

        /** @var Outlines $outlines */
        $outlines = $gantry['outlines'];
        $name = $this->extension->name;

        // Update positions in manifest file.
        $positions = $outlines->positions();

        $manifest = new Manifest($name);
        $manifest->setPositions(array_keys($positions));
        $manifest->save();
    }

    protected function addMenuItems($menutype, array $items, $parent)
    {
        foreach ($items as $alias => $item) {
            $item = (array) $item;
            $item += [
                'menutype' => $menutype,
                'title' => ucfirst($alias),
                'alias' => $alias
            ];

            $outline = isset($item['outline']) ? $item['outline'] : (isset($item['layout']) ? $item['layout'] : null);
            $params = $this->getOutline($outline);
            if (!is_array($params)) {
                $params = [
                    'preset' => isset($item['preset']) ? $item['preset'] : null,
                    'title' => isset($item['style']) ? $this->getStyleName($item['style']) : null
                ];
            }

            $id = $outline ? $this->createOutline($outline, $params) : 0;
            $item['template_style_id'] = (string)(int) $id === (string) $id ? $id : 0;

            // If $parent = 0, do dry run.
            $itemId = $parent ? $this->addMenuItem($item, $parent, true) : 0;
            if (!empty($item['items'])) {
                $this->addMenuItems($menutype, $item['items'], $itemId);
            }
        }
    }

    /**
     * @param string $layout
     * @param string $id
     * @return bool True if files were copied over.
     */
    protected function copyCustom($layout, $id)
    {
        $path = $this->getPath();

        // Only copy files if the target id doesn't exist.
        $dst = $path . '/custom/config/' . $id;
        if (!$layout || !$id || is_dir($dst)) {
            return false;
        }

        // New location for G5.3.2+
        $src = $path . '/install/outlines/' . $layout;
        if (!is_dir($src)) {
            // Old and deprecated location.
            $src = $path . '/install/layouts/' . $layout;
        }

        return is_dir($src) ? \JFolder::copy($src, $dst) : \JFolder::create($dst);
    }

    protected function getInstallerScript()
    {
        if (!$this->script) {
            $className = $this->extension->name . 'InstallerScript';

            if (!class_exists($className)) {
                $manifest = new Manifest($this->extension->name);
                $file = $manifest->getScriptFile();

                $path = "{$this->getPath()}/{$file}";
                if ($file && is_file($path)) {
                    require_once $path;
                }
            }

            if (class_exists($className)) {
                $this->script = new $className;
            }
        }

        return $this->script;
    }

    protected function getManifest()
    {
        if (!$this->manifest) {
            $this->manifest = new Manifest($this->extension->name);
        }

        return $this->manifest;
    }

    protected function getComponent()
    {
        static $component_id;

        if (!$component_id) {
            // Get Gantry component id.
            $component_id = \JComponentHelper::getComponent('com_gantry5')->id;
        }

        return $component_id;
    }
}
