<?php
namespace Gantry\Framework;

use Gantry\Admin\Theme\ThemeList;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Platform as BasePlatform;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    public function getCachePath()
    {
        return Folder::getRelativePath(JPATH_CACHE) . '/gantry5';
    }

    public function getThemesPaths()
    {
        return ['' => ['templates']];
    }

    public function getMediaPaths()
    {
        return ['' => ['gantry-theme://images', 'media/gantry']];
    }

    public function getEnginesPaths()
    {
        return ['' => ['media/gantry/engines']];
    }

    public function getEnginePaths()
    {
        return ['' => ['gantry-theme://engine', 'gantry-engines://nucleus']];
    }

    public function getAssetsPaths()
    {
        return ['' => ['gantry-theme://', 'media/gantry/assets']];
    }

    public function getModules($position)
    {
        // TODO:
        return [];
    }

    public function settings()
    {
        return \JRoute::_('index.php?option=com_config&view=component&component=com_gantryadmin', false);
    }

    public function update()
    {
        return \JRoute::_('index.php?option=com_installer&view=update', false);
    }

    public function updates()
    {
        $styles = ThemeList::getStyles();
        $extension_ids = array_unique(array_map(
            function($item) {
                return (int) $item->extension_id;
            },
            $styles));

        // FIXME: this is just to make something visible...
        $extension_ids[] = 0;

        $extension_ids = $extension_ids ? implode(',', $extension_ids) : '-1';

        $db = \JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select('*')
            ->from('#__updates')
            ->where("element='com_gantryadmin' OR extension_id IN ($extension_ids)");

        $db->setQuery($query);

        $updates = $db->loadObjectList();

        $list = [];
        foreach ($updates as $update)
        {
            $list[] = $update->name . ' ' . $update->version;
        }

        return $list;
    }
}
