<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Admin\ThemeList;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Platform as BasePlatform;
use Joomla\Registry\Registry;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    protected $name = 'joomla';
    protected $settings_key = 'return';
    protected $modules;

    public function getCachePath()
    {
        // Cannot use JPATH_CACHE as it points to admin/site depending where you are.
        return 'cache/gantry5';
    }

    public function getThemesPaths()
    {
        return ['' => ['templates']];
    }

    public function getMediaPaths()
    {
        return ['' => ['gantry-theme://images', 'images', 'media/gantry5']];
    }

    public function getEnginesPaths()
    {
        if (is_link(GANTRY5_ROOT . '/media/gantry5/engines')) {
            // Development environment.
            return ['' => ["media/gantry5/engines/{$this->name}", 'media/gantry5/engines/common']];
        }
        return ['' => ['media/gantry5/engines']];
    }

    public function getAssetsPaths()
    {
        if (is_link(GANTRY5_ROOT . '/media/gantry5/assets')) {
            // Development environment.
            return ['' => ['gantry-theme://', "media/gantry5/assets/{$this->name}", 'media/gantry5/assets/common']];
        }

        return ['' => ['gantry-theme://', 'media/gantry5/assets']];
    }

    public function finalize()
    {
        Document::registerAssets();
    }

    public function countModules($position)
    {
        $document = \JFactory::getDocument();
        return ($document instanceof \JDocumentHTML) ? $document->countModules($position) : 0;
    }

    public function getModules($position)
    {
        // TODO:
        return [];
    }

    public function displayModule($id, $attribs = [])
    {
        $document = \JFactory::getDocument();
        if (!$document instanceof \JDocumentHTML) {
            return '';
        }

        $module = $this->getModule($id);
        $renderer = $document->loadRenderer('module');
        $html = $renderer->render($module, $attribs);

        // Add frontend editing feature as it has only been defined for module positions.
        $app = \JFactory::getApplication();
        $user = \JFactory::getUser();

        $frontEditing = ($app->isSite() && $app->get('frontediting', 1) && !$user->guest);
        $menusEditing = ($app->get('frontediting', 1) == 2) && $user->authorise('core.edit', 'com_menus');

        if ($frontEditing && trim($html) != ''
            && $user->authorise('module.edit.frontend', 'com_modules.module.' . $module->id)) {
            $displayData = [
                'moduleHtml' => &$html,
                'module' => $module,
                'position' => isset($attribs['position']) ? $attribs['position'] : $module->position,
                'menusediting' => $menusEditing
            ];
            \JLayoutHelper::render('joomla.edit.frontediting_modules', $displayData);
        }

        return $html;
    }

    public function displayModules($position, $params = [])
    {
        $document = \JFactory::getDocument();
        if (!$document instanceof \JDocumentHTML) {
            return '';
        }

        $renderer = $document->loadRenderer('modules');

        return $renderer->render($position, $params);
    }

    public function displaySystemMessages($params = [])
    {
        $document = \JFactory::getDocument();
        if (!$document instanceof \JDocumentHTML) {
            return '';
        }

        $renderer = $document->loadRenderer('message');

        return $renderer->render(null, $params);
    }

    public function displayContent($content, $params = [])
    {
        $document = \JFactory::getDocument();
        if (!$document instanceof \JDocumentHTML) {
            return $content;
        }

        $renderer = $document->loadRenderer('component');

        return $renderer->render(null, $params, $content ?: $document->getBuffer('component'));
    }

    protected function getModule($id)
    {
        $modules = $this->getModuleList();
        return $id && isset($modules[$id]) ? $modules[$id] : null;
    }

    protected function &getModuleList()
    {
        if ($this->modules === null) {
            $modules = \JModuleHelper::getModuleList();

            $this->modules = [];
            foreach ($modules as $module) {
                $this->modules[$module->id] = $module;
            }
        }
        return $this->modules;
    }

    public function listModules()
    {
        $db = \JFactory::getDbo();
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

    public function errorHandlerPaths()
    {
        return ['|gantry5|'];
    }

    public function settings()
    {
        return \JRoute::_('index.php?option=com_config&view=component&component=com_gantry5', false);
    }

    public function update()
    {
        return \JRoute::_('index.php?option=com_installer&view=update', false);
    }

    public function updates()
    {
        $styles = ThemeList::getThemes();
        $extension_ids = array_unique(array_map(
            function($item) {
                return (int) $item->extension_id;
            },
            $styles));

        $extension_ids = $extension_ids ? implode(',', $extension_ids) : '-1';

        $db = \JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select('*')
            ->from('#__updates')
            ->where("element='pkg_gantry5' OR extension_id IN ($extension_ids)");

        $db->setQuery($query);

        $updates = $db->loadObjectList();

        $list = [];
        foreach ($updates as $update)
        {
            // Remove number from Gantry 5.
            if ($update->element == 'pkg_gantry5') {
                $update->name = preg_replace('|[\d\s]|', '', $update->name);
            }
            $list[] = $update->name . ' ' . $update->version;
        }

        return $list;
    }
}
