<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\MenuItem;

use Gantry\Joomla\Object\AbstractObject;
use Joomla\CMS\Factory;

/**
 * Class ContactDetails
 * @package Gantry\Joomla\MenuItem
 */
class MenuItem extends AbstractObject
{
    /** @var array */
    static protected $instances = [];
    /** @var string */
    static protected $table = 'Menu';
    /** @var string */
    static protected $order = 'id';

    public function exportSql()
    {
        $component = $this->component_id;
        if ($component) {
            $components = static::getComponents();
            $component = $components[$component]->name;

            $array = $this->getFieldValues(['asset_id', 'checked_out', 'checked_out_time']);
            $array['`component_id`'] = '`extension_id`';

            $keys = implode(',', array_keys($array));
            $values = implode(',', array_values($array));

            return "INSERT INTO `#__menu` ($keys)\nSELECT {$values}\nFROM `#__extensions` WHERE `name` = '{$component}';";
        }

        return $this->getCreateSql(['asset_id']) . ';';
    }

    protected static function getComponents()
    {
        static $components;

        if (null === $components) {
            $db = Factory::getDbo();

            $query = $db->getQuery(true);
            $query->select('extension_id, name')->from('#__extensions');

            $components = $db->setQuery($query)->loadObjectList('extension_id');
        }

        return $components;
    }
}
