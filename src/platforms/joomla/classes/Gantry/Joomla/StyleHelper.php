<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Gantry;
use Gantry\Framework\ThemeInstaller;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\Templates\Administrator\Model\StyleModel;
use Joomla\Component\Templates\Administrator\Table\StyleTable;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Joomla style helper.
 */
class StyleHelper
{
    /**
     * @param int|array|null $id
     * @return StyleTable
     */
    public static function getStyle($id = null)
    {
        /** @var StyleTable $table */
        $table = Factory::getApplication()->bootComponent('com_templates')
                ->getMVCFactory()->createTable('Style', 'Administrator');

        if (null !== $id) {
            if (!\is_array($id)) {
                $id = ['id' => $id, 'client_id' => 0];
            }

            $table->load($id);
        }

        return $table;
    }

    /**
     * @param string $template
     * @return array
     */
    public static function loadStyles($template)
    {
        $db = Factory::getDbo();

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
     * @return StyleTable
     */
    public static function getDefaultStyle()
    {
        return static::getStyle(['home' => 1, 'client_id' => 0]);
    }

    /**
     * @param ThemeDetails|StyleTable $style
     * @param string $old
     * @param string $new
     */
    public static function copy($style, $old, $new)
    {
        $gantry = Gantry::instance();

        $name = $style instanceof ThemeDetails ? $style->name : $style->template;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $oldPath = $locator->findResource('gantry-config://' . $old, true, true);
        $newPath = $locator->findResource('gantry-config://' . $new, true, true);

        if (\file_exists($oldPath)) {
            Folder::copy($oldPath, $newPath);
        }

        $installer = new ThemeInstaller($name);
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

        $installer = new ThemeInstaller($style->template);
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

        if (\is_dir($path)) {
            Folder::delete($path, true);
        }
    }

    /**
     * @param string $name
     * @return StyleModel
     */
    public static function loadModel(): StyleModel
    {
        static $model;

        if (!isset($model)) {
            /** @var \Joomla\Component\Templates\Administrator\Model\StyleModel $model */
            $model = Factory::getApplication()->bootComponent('com_templates')->getMVCFactory()
                ->createModel('Style', 'Administrator', ['ignore_request' => true]);
        }

        return $model;
    }
}
