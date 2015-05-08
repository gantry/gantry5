<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

use RocketTheme\Toolbox\File\YamlFile;

class TemplateInstaller
{
    protected $extension;

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

    public function getStyleName($title)
    {
        return \JText::sprintf($title, \JText::_($this->extension->name));
    }

    public function createStyle()
    {
        $style = \JTable::getInstance('Style', 'TemplatesTable');
        $style->reset();
        $style->template = $this->extension->element;
        $style->client_id = $this->extension->client_id;

        return $style;
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
        $style->load(['home' => 1]);

        return $style;
    }

    public function updateStyle($name, array $configuration, $home = null)
    {
        $style = $this->getStyle($name);

        if ($style->id) {
            $home = ($home !== null ? $home : $style->home);
            $params = (array) json_decode($style->params, true);

            $data = array(
                'params' => json_encode($configuration + $params),
                'home' => $home
            );

            $style->save($data);
        }

        return $style;
    }

    public function addStyle($title, array $configuration, $home = 0)
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

        // Defaults for the item.
        $item += [
            'menutype'     => 'mainmenu',
            'title'        => 'Home',
            'alias'        => 'gantry5',
            'link'         => 'index.php?option=com_gantry5&view=custom',
            'type'         => 'component',
            'published'    => 1,
            'parent_id'    => $parent_id,
            'component_id' => $component_id,
            'access'       => 1,
            'template_style_id' => 0,
            'params'       => '{}',
            'home'         => 0,
            'language'     => '*',
            'client_id'    => 0
        ];

        if (in_array($item['type'], ['separator', 'heading'])) {
            $item['link'] = '';
        }
        if ($item['type'] !== 'component') {
            $item['component_id'] = 0;
        }

        if ($load) {
            $table->load([
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

        return $table->id;
    }

    /**
     * @param string $type
     * @return \JTableMenu
     */
    public function getMenu($type)
    {
         /** @var \JTableMenu $table */
        $table = \JTable::getInstance('MenuType');
        $table->load(['menutype' => $type]);

        return $table;
    }

    /**
     * @param string $type
     * @param string $title
     * @param string $description
     * @throws \Exception
     */
    public function createMenu($type, $title, $description)
    {
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

    public function cleanup()
    {
        $name = $this->extension->name;
        $path = JPATH_SITE . '/templates/' . $name;

        $cssPath = $path . '/custom/css-compiled';
        if (is_dir($cssPath)) {
            \JFolder::delete($cssPath);
        }
    }

    public function installMenus(array $menus = null, $parent = 1)
    {
        if ($menus === null) {
            $name = $this->extension->name;
            $path = JPATH_SITE . '/templates/' . $name;

            $menus = (array) YamlFile::instance($path . '/install/menus.yaml')->content();
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

    protected function addMenuItems($menutype, array $items, $parent)
    {
        foreach ($items as $alias => $item) {
            $item = (array) $item;
            $item += [
                'menutype' => $menutype,
                'title' => ucfirst($alias),
                'alias' => $alias
            ];

            if (isset($item['layout']) && $item['layout'][0] !== '_') {
                $styleName = '%s - ' . ucwords(trim(strtr($item['layout'], ['_' => ' '])));
                $styleName = $this->getStyleName($styleName);
                $style = $this->getStyle($styleName);

                if (!$style->id) {
                    $style = $this->addStyle($styleName, ['preset' => $item['layout']]);
                } else {
                    $style = $this->updateStyle($styleName, ['preset' => $item['layout']]);
                }

                $item['template_style_id'] = $style->id;
            }

            // If $parent = 0, do dry run.
            $itemId = $parent ? $this->addMenuItem($item, $parent, true) : 0;
            if (!empty($item['items'])) {
                $this->addMenuItems($menutype, $item['items'], $itemId);
            }
        }
    }
}
