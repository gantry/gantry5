<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Gantry;
use Gantry\Framework\ThemeInstaller;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Component\Templates\Administrator\Model\StyleModel;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Joomla style helper.
 */
class StyleHelper
{
    /**
     * @param int|array $id
     * @return bool|\Joomla\CMS\Table\Table
     * @throws \Exception
     */
    public static function getStyle($id)
    {
        if (version_compare(JVERSION, '4', '<')) {
            // Joomla 3 support.
            Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');

            $style = Table::getInstance('Style', 'TemplatesTable');
        } else {
            $model = static::loadModel();
            $style = $model->getTable('Style');
        }

        if (!is_array($id)) {
            $id = ['id' => $id, 'client_id' => 0];
        }

        $style->load($id);

        return $style;
    }

    /**
     * @param string $template
     * @return array
     */
    public static function loadStyles($template)
    {
        $db = JoomlaFactory::getDbo();

        $query = $db
            ->getQuery(true)
            ->select('s.id, s.template, s.home, s.title AS long_title, s.params')
            ->from('#__template_styles AS s')
            ->where('s.client_id = 0')
            ->where("s.template = {$db->quote($template)}")
            ->order('s.id');

        $db->setQuery($query);

        $list = $db->loadObjectList('id') ?: [];

        foreach ($list as $id => &$style) {
            $style->title = preg_replace('/' . preg_quote(Text::_($style->template), '/') . '\s*-\s*/u', '', $style->long_title);
            $style->home = $style->home && $style->home !== '1' ? $style->home : (bool)$style->home;
        }

        return $list;
    }

    /**
     * @return bool|\Joomla\CMS\Table\Table
     * @throws \Exception
     */
    public static function getDefaultStyle()
    {
        return static::getStyle(['home' => 1, 'client_id' => 0]);
    }

    /**
     * @param object $style
     * @param string $old
     * @param string $new
     */
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

    /**
     * @param int|array $id
     * @param mixed $preset
     * @throws \Exception
     */
    public static function update($id, $preset)
    {
        $style = static::getStyle($id);

        $extension = !empty($style->extension_id) ? $style->extension_id : $style->template;

        $installer = new ThemeInstaller($extension);
        $installer->updateStyle($id, ['configuration' => $id, 'preset' => $preset]);
    }

    /**
     * @param string $id
     */
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

                Table::addIncludePath("{$path}/tables");
                require_once "{$path}/models/style.php";

                // Load language strings.
                $lang = JoomlaFactory::getLanguage();
                $lang->load('com_templates');

                $model = new \TemplatesModelStyle;
            } else {
                // Joomla 4 support.
                $app = JoomlaFactory::getApplication();
                $model = $app->bootComponent('com_templates')
                    ->getMVCFactory()
                    ->createModel('Style', 'Administrator', ['ignore_request' => true]);
            }

            return $model;
        }

        return $model;
    }
}
