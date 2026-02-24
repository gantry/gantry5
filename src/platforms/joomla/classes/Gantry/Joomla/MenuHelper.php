<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework)
 * @currentDeveloper  Tiger12, LLC
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Menu;
use Joomla\CMS\Table\MenuType;
use Joomla\CMS\Table\Table;
use Joomla\Component\Menus\Administrator\Model\ItemModel; // Joomla 4
use Joomla\Component\Menus\Administrator\Table\MenuTable; // Joomla 4
use Joomla\Component\Menus\Administrator\Table\MenuTypeTable; // Joomla 4

/**
 * Joomla style helper.
 */
class MenuHelper
{
    /**
     * @param int|array|null $id
     * @return \JTableMenu|MenuTable|\Joomla\CMS\Table\Menu
     */
    public static function getMenu($id = null)
    {
        $model = static::loadModel();
        $table = $model->getTable();

        if (null !== $id) {
            if (!is_array($id)) {
                $id = ['id' => $id, 'client_id' => 0];
            }

            $table->load($id);
        }

        return $table;
    }

    /**
     * @param int|array|null $id
     * @return \JTableMenuType|MenuTypeTable|\Joomla\CMS\Table\MenuType
     */
    public static function getMenuType($id = null)
    {
        $model = static::loadModel();
        $table = $model->getTable('MenuType');

        if (null !== $id) {
            if (!is_array($id)) {
                $id = ['menutype' => $id];
            }

            $table->load($id);
        }

        return $table;
    }

    /**
     * @param string $name
     * @return ItemModel|\MenusModelItem
     */
    private static function loadModel($name = 'Item')
    {
        static $model = [];

        if (!isset($model[$name])) {
            // Joomla 5+ (use modern component boot and MVC factory)
            $application = Factory::getApplication();
            $model[$name] = $application->bootComponent('com_menus')
                ->getMVCFactory()
                ->createModel($name, 'Administrator', ['ignore_request' => true]);
        }

        return $model[$name];
    }
}
