<?php

class Gantry_Adminblock_Block_Adminhtml_Adminblock extends Mage_Core_Block_Template
{

    protected function loadGantry()
    {
        if (!defined('GANTRYADMIN_PATH'))
        {
            define('GANTRYADMIN_PATH', dirname(dirname(dirname(__DIR__))));
        }

        // Bootstrap Gantry framework or fail gracefully (inside included file).
        $gantry = include GANTRYADMIN_PATH . '/includes/gantry.php';

        $gantry['router'] = function ($c)
        {
            return new \Gantry\Admin\Router($c);
        };

        return $gantry;
    }

}
