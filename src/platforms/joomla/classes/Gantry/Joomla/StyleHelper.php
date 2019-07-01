<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Gantry;
use Gantry\Framework\ThemeInstaller;
use Joomla\Component\Templates\Administrator\Model\StyleModel;
use Joomla\Component\Templates\Administrator\Table\StyleTable;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Joomla style helper.
 */
class StyleHelper
{
    public static function getStyle($id)
    {
        if (version_compare(JVERSION, '4', '<')) {
            // Joomla 3 support.
            \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');

            $style = \JTable::getInstance('Style', 'TemplatesTable');
        } else {
            $style = new StyleTable(\JFactory::getDbo());
        }
        $style->load($id);

        return $style;
    }

    public static function loadStyles($template)
    {
        $db = \JFactory::getDbo();

        $query = $db
            ->getQuery(true)
            ->select('s.id, s.template, s.home, s.title AS long_title, s.params')
            ->from('#__template_styles AS s')
            ->where('s.client_id = 0')
            ->where("s.template = {$db->quote($template)}")
            ->order('s.id');

        $db->setQuery($query);

        $list = (array) $db->loadObjectList('id');

        foreach ($list as $id => &$style) {
            $style->title = preg_replace('/' . preg_quote(\JText::_($style->template), '/') . '\s*-\s*/u', '', $style->long_title);
            $style->home = $style->home && $style->home !== '1' ? $style->home : (bool)$style->home;
        }

        return $list;
    }

    public static function getDefaultStyle()
    {
        return static::getStyle(['home' => 1, 'client_id' => 0]);
    }

    public static function copy($style, $old, $new)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $oldPath = $locator->findResource('gantry-config://' . $old, true, true);
        $newPath = $locator->findResource('gantry-config://' . $new, true, true);

        if (file_exists($oldPath)) {
            Folder::copy($oldPath, $newPath);
        }

        $extension = !empty($style->extension_id) ? $style->extension_id : $style->template;

        $installer = new ThemeInstaller($extension);
        $installer->updateStyle($new, ['configuration' => $new]);
    }

    public static function update($id, $preset)
    {
        $style = static::getStyle($id);

        $extension = !empty($style->extension_id) ? $style->extension_id : $style->template;

        $installer = new ThemeInstaller($extension);
        $installer->updateStyle($id, ['configuration' => $id, 'preset' => $preset]);
    }

    public static function delete($id)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $path = $locator->findResource('gantry-config://' . $id, true, true);

        if (is_dir($path)) {
            Folder::delete($path, true);
        }
    }

    /**
     * @return \TemplatesModelStyle|StyleModel
     */
    public static function loadModel()
    {
        static $model;

        if (!$model) {
            if (version_compare(JVERSION, '4', '<')) {
                // Joomla 3 support.
                $path = JPATH_ADMINISTRATOR . '/components/com_templates/';

                \JTable::addIncludePath("{$path}/tables");
                require_once "{$path}/models/style.php";

                // Load language strings.
                $lang = \JFactory::getLanguage();
                $lang->load('com_templates');

                $model = new \TemplatesModelStyle;
            } else {
                // Joomla 4 support.
                $app = \JFactory::getApplication();
                $model = $app->bootComponent('com_templates')
                    ->getMVCFactory()
                    ->createModel('Style', 'Administrator', ['ignore_request' => true]);
            }

            return $model;
        }

        return $model;
    }
}
