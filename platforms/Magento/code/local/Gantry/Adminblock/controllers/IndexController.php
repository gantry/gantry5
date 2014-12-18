<?php

class Gantry_Adminblock_IndexController extends Mage_Adminhtml_Controller_Action {

    protected function indexAction(){

        $this->loadLayout()
        ->_setActiveMenu('gantry_menu')
        ->_title($this->__('Gantry'))
        ->_addContent(
            $this->getLayout()->createBlock('gantry_adminblock/adminhtml_adminblock')->setTemplate('gantry/adminblock.phtml')
        )
        ->renderLayout();
    }

}