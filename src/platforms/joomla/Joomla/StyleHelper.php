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

use Gantry\Admin\ThemeList;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout;
use Gantry\Framework\Gantry;
use Gantry\Joomla\TemplateInstaller;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Joomla manifest file modifier.
 */
class StyleHelper
{
    public static function getStyle($id)
    {
        \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');

        $style = \JTable::getInstance('Style', 'TemplatesTable');
        $style->load($id);

        return $style;
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

        $installer = new TemplateInstaller($style->extension_id);
        $installer->updateStyle($new, ['configuration' => $new]);
    }

    /**
     * @return \TemplatesModelStyle
     */
    public static function loadModel()
    {
        static $model;

        if (!$model) {
            $path = JPATH_ADMINISTRATOR . '/components/com_templates/';

            \JTable::addIncludePath("{$path}/tables");
            require_once "{$path}/models/style.php";

            // Load language strings.
            $lang = \JFactory::getLanguage();
            $lang->load('com_templates');

            $model = new \TemplatesModelStyle;
        }

        return $model;
    }
}
