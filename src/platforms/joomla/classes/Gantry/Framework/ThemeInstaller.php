<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Layout\Layout;
use Gantry\Component\Theme\ThemeInstaller as AbstractInstaller;
use Gantry\Joomla\CacheHelper;
use Gantry\Joomla\Manifest;
use Gantry\Joomla\MenuHelper;
use Gantry\Joomla\StyleHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\TemplateAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Extension;
use Joomla\CMS\Table\MenuType;
use Joomla\Component\Menus\Administrator\Table\MenuTypeTable; // Joomla 4
use Joomla\Component\Templates\Administrator\Table\StyleTable; // Joomla 4
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RocketTheme\Toolbox\File\YamlFile;

/**
 * Class ThemeInstaller
 * @package Gantry\Framework
 */
class ThemeInstaller extends AbstractInstaller
{
    use DatabaseAwareTrait;

    /**
     * @var TemplateAdapter
     */
    protected $extension;

    /**
     * @var Manifest
     */
    protected $manifest;

    /**
     * ThemeInstaller constructor.
     * @param TemplateAdapter|string|null $extension
     * @param ?DatabaseInterface $db
     */
    public function __construct($extension = null, ?DatabaseInterface $db = null)
    {
        parent::__construct();

        if ($db === null) {
            @trigger_error(\sprintf('Database will be mandatory in 5.7.'), E_USER_DEPRECATED);
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        }

        $this->setDatabase($db);

        if ($extension instanceof TemplateAdapter) {
            $this->setInstaller($extension);
        } elseif ($extension) {
            $this->loadExtension($extension);
        }
    }

    /**
     * @param TemplateAdapter $install
     * @return $this
     * @throws \ReflectionException
     */
    public function setInstaller(TemplateAdapter $install)
    {
        // We need access to a protected variable $install->extension.
        $rProperty = new \ReflectionProperty($install, 'extension');
        $rProperty->setAccessible(true);

        $this->extension = $rProperty->getValue($install);
        $this->name = $this->extension->name;

        $this->manifest = new Manifest($this->extension->name, $install->getManifest());

        return $this;
    }

    /**
     * @param int|string|array $id
     */
    public function loadExtension($id)
    {
        if ((string)(int) $id !== (string) $id) {
            $id = ['type' => 'template', 'element' => (string) $id, 'client_id' => 0];
        }

        /** @var Extension extension */
        $this->extension = new Extension($this->getDatabase());
        $this->extension->load($id);

        $this->name = $this->extension->name;
    }

    /**
     * @param string $template
     * @param array $context
     * @return string
     */
    public function render($template, $context = [])
    {
        $token    = Factory::getApplication()->getFormToken();
        $manifest = $this->getManifest();
        $context += [
            'description' => $this->translate((string) $manifest->get('description')),
            'version' => (string) $manifest->get('version'),
            'date' => (string) $manifest->get('creationDate'),
            'author' => [
                'name' => (string) $manifest->get('author'),
                'email' => (string) $manifest->get('authorEmail'),
                'url' => (string) $manifest->get('authorUrl')
            ],
            'copyright' => (string) $manifest->get('copyright'),
            'license' => (string) $manifest->get('license'),
            'install_url' => Route::_("index.php?option=com_gantry5&view=install&theme={$this->name}&{$token}=1", false),
            'edit_url' => Route::_("index.php?option=com_gantry5&view=configurations/default/layout&theme={$this->name}&{$token}=1", false),
        ];

        return parent::render($template, $context);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return JPATH_SITE . '/templates/' . $this->extension->name;
    }

    /**
     * @param string $title
     * @return string
     */
    public function getStyleName($title): string
    {
        return Text::sprintf($title, Text::_($this->extension->name));
    }

    /**
     * @param string|null $name
     * @return StyleTable
     */
    public function getStyle($name = null): StyleTable
    {
        if (is_numeric($name)) {
            $field = 'id';
        } else {
            $field = 'title';
            $name = $this->getStyleName($name);
        }

        $style = StyleHelper::getStyle([
            'template' => $this->extension->element,
            'client_id' => $this->extension->client_id,
            $field => $name
        ]);

        return $style;
    }

    /**
     * @return StyleTable
     */
    public function getDefaultStyle(): StyleTable
    {
        return StyleHelper::getDefaultStyle();
    }

    /**
     * @param string $type
     * @return MenuTypeTable
     */
    public function getMenu($type): MenuTypeTable
    {
        return MenuHelper::getMenuType($type);
    }

    public function createSampleData()
    {
        $this->updateStyle('JLIB_INSTALLER_DEFAULT_STYLE', [], 1);
        $this->installMenus();
    }

    /**
     * @return StyleTable
     */
    public function createStyle(): StyleTable
    {
        $style = StyleHelper::getStyle();
        $style->reset();
        $style->template = $this->extension->element;
        $style->client_id = $this->extension->client_id;

        return $style;
    }

    /**
     * @param $title
     * @param ?array $configuration
     * @param ?int $home
     * @return StyleTable
     */
    public function addStyle($title, array $configuration = [], $home = 0): StyleTable
    {
        // Make sure language debug is turned off.
        $language = Factory::getApplication()->getLanguage();
        $debug    = $language->setDebug(false);

        // Translate title.
        $title = $this->getStyleName($title);

        // Turn language debug back on.
        $language->setDebug($debug);

        $data = [
            'home' => (int) $home,
            'title' => $title,
            'params' => json_encode($configuration),
        ];

        $style = $this->createStyle();
        $style->save($data);

        if ($home) {
            $this->actions[] = [
                'action' => 'default_style_assigned',
                'text'   => Text::sprintf('GANTRY5_INSTALLER_ACTION_DEFAULT_STYLE_ASSIGNED', $title)
            ];
        }

        return $style;
    }

    /**
     * @param string $name
     * @param ?array $configuration
     * @param ?string|null $home
     * @return StyleTable
     */
    public function updateStyle($name, array $configuration, $home = null): StyleTable
    {
        $style = $this->getStyle($name);

        if ($style->id) {
            $home   = $home !== null ? $home : $style->home;
            $params = (array) \json_decode($style->params, true);

            $data = [
                'params' => \json_encode($configuration + $params),
                'home'   => $home
            ];

            if ($home && !$style->home) {
                $this->actions[] = [
                    'action' => 'default_style_assigned',
                    'text'   => Text::sprintf('GANTRY5_INSTALLER_ACTION_DEFAULT_STYLE_ASSIGNED', $style->title)
                ];
            }

            $style->save($data);
        }

        return $style;
    }

    /**
     * @param StyleTable $style
     */
    public function assignHomeStyle($style): void
    {
        // Update the mapping for menu items that this style IS assigned to.
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->update($db->quoteName('#__menu'))
            ->set($db->quoteName('template_style_id') . ' = :styleid')
            ->where(
                [
                    $db->quoteName('home') . ' = 1',
                    $db->quoteName('client_id') . ' = 0'
                ]
            )
            ->bind(':styleid', $style->id, ParameterType::INTEGER);

        $db->setQuery($query)->execute();

        if ($db->getAffectedRows()) {
            $this->actions[] = [
                'action' => 'home_style_assigned',
                'text' => Text::sprintf('GANTRY5_INSTALLER_ACTION_HOME_STYLE_ASSIGNED', $style->title)
            ];
        }
    }

    /**
     * @param string $folder
     * @param ?array $params
     * @return string|bool
     * @throws \RuntimeException
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

        $title  = $params['title'] ?: \ucwords(\trim(\strtr($folder, ['_' => ' '])));
        $preset = $params['preset'] ?: 'default';

        if ($folder[0] !== '_') {
            $title = $this->getStyleName($title !== 'Default' ? "%s - {$title}" : 'JLIB_INSTALLER_DEFAULT_STYLE');
            $style = $this->getStyle($title);

            if (!$style->id) {
                // Only add style if it doesn't exist.
                $style = $this->addStyle($title, ['preset' => $preset]);
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

                $this->actions[] = [
                    'action' => 'base_outline_created',
                    'text'   => $this->translate('GANTRY5_INSTALLER_ACTION_BASE_OUTLINE_CREATED', $title)
                ];
            }

            $this->actions[] = $created
                ? [
                    'action' => 'outline_created',
                    'text'   => $this->translate('GANTRY5_INSTALLER_ACTION_OUTLINE_CREATED', $title)
                ]
                : [
                    'action' => 'outline_updated',
                    'text'   => $this->translate('GANTRY5_INSTALLER_ACTION_OUTLINE_UPDATED', $title)
                ];

            // Update preset in Joomla table.
            $this->updateStyle($title, ['preset' => $layout->preset['name']]);
        }

        return $id;
    }

    /**
     * @param  array $item       [menutype, title, alias, link, template_style_id, params]
     * @param  ?int   $parent_id  Parent menu id.
     * @param  ?bool  $load       True if updating existing items.
     * @return int
     * @throws \Exception
     */
    public function addMenuItem(array $item, $parent_id = 1, $load = false)
    {
        $component_id = $this->getComponent();

        $table  = MenuHelper::getMenu();
        $update = false;

        $checked_out = null;
        $checked_out_time =  null;

        // Make sure we can store home menu even if the current home is checked out.
        $isHhome = $item['home'];
        unset($item['home']);

        // Defaults for the item.
        $item += [
            'menutype'          => 'mainmenu',
            'title'             => 'Home',
            'alias'             => 'gantry5',
            'note'              => '',
            'link'              => 'index.php?option=com_gantry5&view=custom',
            'type'              => 'component',
            'published'         => 1,
            'parent_id'         => $parent_id,
            'component_id'      => $component_id,
            'checked_out'       => $checked_out,
            'checked_out_time'  => $checked_out_time,
            'browserNav'        => 0,
            'access'            => 1,
            'img'               => '',
            'template_style_id' => 0,
            'params'            => '{}',
            'home'              => 0,
            'language'          => '*',
            'client_id'         => 0
        ];

        if (\in_array($item['type'], ['separator', 'heading'], true)) {
            $item['link'] = '';
        }

        if ($item['type'] !== 'component') {
            $item['component_id'] = 0;
        }

        if ($load) {
            $update = $table->load([
                'menutype'  => $item['menutype'],
                'alias'     => $item['alias'],
                'parent_id' => $item['parent_id']
            ]);
        }

        if (!$update) {
            $table->setLocation($parent_id, 'last-child');

            if (!$table->bind($item) || !$table->check() || !$table->store()) {
                throw new \Exception($table->getError());
            }
        }

        // Turn menu item into home, ignore errors.
        if ($isHhome) {
            $table->home = 1;
            $table->store();
        }

        CacheHelper::cleanMenu();

        $menu = MenuHelper::getMenuType($item['menutype']);

        if (!isset($this->actions["menu_{$item['menutype']}_created"])) {
            $postfix = $item['home'] ? '_HOME' : '';

            $this->actions[] = $update
                ? [
                    'action' => 'menu_item_updated',
                    'text'   => Text::sprintf('GANTRY5_INSTALLER_ACTION_MENU_ITEM_UPDATED' . $postfix, $table->title, $table->path, $menu->title)
                ]
                : [
                    'action' => 'menu_item_created',
                    'text'   => Text::sprintf('GANTRY5_INSTALLER_ACTION_MENU_ITEM_CREATED' . $postfix, $table->title, $table->path, $menu->title)
                ];
        } elseif ($item['home']) {
            $this->actions[] = [
                'action' => 'menu_item_updated',
                'text'   => Text::sprintf('GANTRY5_INSTALLER_ACTION_MENU_ITEM_HOME', $table->title, $table->path, $menu->title)
            ];
        }

        return $table->id;
    }

    /**
     * @param ?array|null $menus
     * @param ?int $parent
     * @throws \RuntimeException
     */
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
     * @throws \RuntimeException
     */
    public function createMenu($type, $title, $description)
    {
        /** @var MenuType $table */
        $table = MenuHelper::getMenuType();
        $data  = [
            'menutype'    => $type,
            'title'       => $title,
            'description' => $description
        ];

        if (!$table->bind($data) || !$table->check()) {
            // Menu already exists, do nothing
            return;
        }

        if (!$table->store()) {
            throw new \RuntimeException($table->getError());
        }

        $this->actions["menu_{$type}_created"] = [
            'action' => 'menu_created',
            'text'   => Text::sprintf('GANTRY5_INSTALLER_ACTION_MENU_CREATED', $title)
        ];
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

        $table = MenuHelper::getMenuType($type);

        if ($table->id) {
            $success = $table->delete();

            if (!$success) {
                Factory::getApplication()->enqueueMessage($table->getError(), 'error');
            } else {
                $this->actions["menu_{$type}_deleted"] = [
                    'action' => 'menu_delete',
                    'text'   => Text::_('GANTRY5_INSTALLER_ACTION_MENU_DELETED', $table->title)
                ];
            }
        }

        CacheHelper::cleanMenu();
    }

    /**
     * @param string $type
     */
    public function unsetHome($type): void
    {
        // Update the mapping for menu items that this style IS assigned to.
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->update($db->quoteName('#__menu'))
            ->set($db->quoteName('home') . ' = 0')
            ->where(
                [
                    $db->quoteName('menutype') . ' = :type',
                    $db->quoteName('client_id') . ' = 0'
                ]
            )
            ->bind(':type', $type);

        $db->setQuery($query)->execute();
    }

    /**
     * @return void
     */
    public function finalize(): void
    {
        parent::finalize();

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

    /**
     * @param $menutype
     * @param array $items
     * @param $parent
     * @throws \Exception
     */
    protected function addMenuItems($menutype, array $items, $parent): void
    {
        foreach ($items as $alias => $item) {
            $item = (array) $item;
            $item += [
                'menutype' => $menutype,
                'title' => ucfirst($alias),
                'alias' => $alias
            ];

            $outline = $item['outline'] ?? ($item['layout'] ?? null);
            $params  = $this->getOutline($outline);

            if (!\is_array($params)) {
                $params = [
                    'preset' => $item['preset'] ?? ($item['layout'] ?? null),
                    'title'  => $item['style'] ?? null
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
     * @return object
     */
    protected function getInstallerScript()
    {
        if (!$this->script) {
            $className = $this->extension->name . 'InstallerScript';

            if (!\class_exists($className)) {
                $manifest = new Manifest($this->extension->name);
                $file     = $manifest->getScriptFile();
                $path     = "{$this->getPath()}/{$file}";

                if ($file && \is_file($path)) {
                    require_once $path;
                }
            }

            if (\class_exists($className)) {
                $this->script = new $className();
            }
        }

        return $this->script;
    }

    /**
     * @return Manifest
     */
    protected function getManifest(): Manifest
    {
        if (!$this->manifest) {
            $this->manifest = new Manifest($this->extension->name);
        }

        return $this->manifest;
    }

    /**
     * @return int
     */
    protected function getComponent(): int
    {
        static $component_id;

        if (!$component_id) {
            // Get Gantry component id.
            $component_id = ComponentHelper::getComponent('com_gantry5')->id;
        }

        return $component_id;
    }
}
