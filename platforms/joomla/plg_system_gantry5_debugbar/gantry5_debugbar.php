<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

class plgSystemGantry5_Debugbar extends CMSPlugin
{
    public function __construct(&$subject, $config = [])
    {
        require_once __DIR__ . '/Debugger.php';

        parent::__construct($subject, $config);
    }
}
