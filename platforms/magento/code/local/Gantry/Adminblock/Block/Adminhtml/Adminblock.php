<?php

class Gantry_Adminblock_Block_Adminhtml_Adminblock extends Mage_Core_Block_Template
{

    protected function loadGantry()
    {
        // Bootstrap Gantry framework or fail gracefully (inside included file).
        $gantry = include dirname(dirname(dirname(__DIR__))) . '/includes/gantry.php';

        if (!defined('GANTRYADMIN_PATH'))
        {
            define('GANTRYADMIN_PATH', GANTRY5_ROOT . '/app/code/local/Gantry');
        }

        // FIXME: add base url for Gantry admin.
        $gantry['base_url'] =  '/';

        $gantry['router'] = function ($c)
        {
            return new \Gantry\Admin\Router($c);
        };

        return $gantry;
    }

}
