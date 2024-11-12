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

use Joomla\CMS\Factory;
use Joomla\Component\Menus\Administrator\Table\MenuTable;
use Joomla\Component\Menus\Administrator\Table\MenuTypeTable;

/**
 * Joomla style helper.
 */
class MenuHelper
{
    /**
     * @param ?int|array $id
     * @return MenuTable
     */
    public static function getMenu($id = null): MenuTable
    {
        /** @var MenuTable $table */
        $table = Factory::getApplication()->bootComponent('com_menus')
                ->getMVCFactory()->createTable('Menu', 'Administrator');

        if (null !== $id) {
            if (!\is_array($id)) {
                $id = ['id' => $id, 'client_id' => 0];
            }

            $table->load($id);
        }

        return $table;
    }

    /**
     * @param ?int|array $id
     * @return MenuTypeTable
     */
    public static function getMenuType($id = null): MenuTypeTable
    {
        /** @var MenuTypeTable $table */
        $table = Factory::getApplication()->bootComponent('com_menus')
                ->getMVCFactory()->createTable('MenuType', 'Administrator');

        if (null !== $id) {
            if (!\is_array($id)) {
                $id = ['menutype' => $id];
            }

            $table->load($id);
        }

        return $table;
    }
}
