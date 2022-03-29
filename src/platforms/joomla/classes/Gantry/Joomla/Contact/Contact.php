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
}
