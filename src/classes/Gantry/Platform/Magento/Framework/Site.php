<?php
namespace Gantry\Framework;

class Site
{
    public function __construct()
    {
        $this->url = \Mage::getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_WEB);
        $this->title = 'Title';
        $this->description = 'Description';
    }
}
