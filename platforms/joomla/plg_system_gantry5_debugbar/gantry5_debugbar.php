<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

class plgSystemGantry5_Debugbar extends JPlugin
{
    public function __construct(&$subject, $config = array())
    {
        require_once __DIR__ . '/Debugger.php';

        parent::__construct($subject, $config);
    }
}
