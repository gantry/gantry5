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

class TemplateInstaller
{
    protected $extension;

    public function __construct($extension = null)
    {
        \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
        if (is_numeric($extension)) {
            $this->loadExtension($extension);
        } elseif ($extension instanceof \JInstallerAdapterTemplate) {
            $this->setInstaller($extension);
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

            $data = array(
                'params' => json_encode($configuration),
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
     * @param array $item [menutype, title, alias, link, template_style_id, params]
     * @throws \Exception
     */
    public function addMenuItem(array $item)
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
            'parent_id'    => 1,
            'component_id' => $component_id,
            'access'       => 1,
            'template_style_id' => 0,
            'params'       => '{}',
            'home'         => 0,
            'language'     => '*',
            'client_id'    => 0
        ];

        $table->setLocation(1, 'last-child');

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
}
