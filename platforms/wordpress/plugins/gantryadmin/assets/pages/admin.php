<?php
 /**
  * @version   $Id$
  * @author    RocketTheme http://www.rockettheme.com
  * @copyright Copyright (C) 2007 - ${copyright_year} RocketTheme, LLC
  * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
  */
if (isset($_GET['subview'])) {
    $subview = (string)urldecode($_GET['subview']);
} else{
    $subview = 'overview';
}

include('gantry-admin://assets/sections/main.php');