<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\ContactDetails;

use Gantry\Joomla\Object\AbstractObject;

/**
 * Class ContactDetails
 * @package Gantry\Joomla\ContactDetails
 */
class ContactDetails extends AbstractObject
{
    /** @var array */
    static protected $instances = [];
    /** @var string */
    static protected $table = 'ContactDetails';
    /** @var string */
    static protected $order = 'id';

    public function exportSql()
    {
        return $this->getCreateSql(['asset_id']) . ';';
    }
}
