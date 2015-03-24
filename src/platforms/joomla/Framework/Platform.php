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
    protected $name = 'joomla';

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
        return ['' => ['gantry-theme://images', 'media/gantry5']];
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
            ->where("element='com_gantry5' OR extension_id IN ($extension_ids)");

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
