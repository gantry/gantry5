<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Contact;

use Gantry\Joomla\Object\AbstractObject;
use Joomla\CMS\Table\Table;

/**
 * Class Contact
 * @package Gantry\Joomla\Contact
 */
class Contact extends AbstractObject
{
    /** @var array */
    static protected $instances = [];
    /** @var string */
    static protected $table = 'ContactTable';
    static protected $tablePrefix = 'Joomla\Component\Contact\Administrator\Table\\';
    /** @var string */
    static protected $order = 'id';

    public function exportSql()
    {
        return $this->getCreateSql(['asset_id', 'checked_out', 'checked_out_time', 'created_by', 'modified_by', 'publish_up', 'publish_down', 'version', 'hits']) . ';';
    }

    /**
     * Method to get the table object.
     *
     * @return  Table  The table object.
     */
    protected static function getTable()
    {
        if (\JVersion::MAJOR_VERSION === 3) {
            require_once JPATH_ADMINISTRATOR . '/components/com_contact/tables/contact.php';

            static::$table = 'Contact';
            static::$tablePrefix = 'ContactTable';
        }

        return parent::getTable();
    }
}
