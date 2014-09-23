<?php
namespace Gantry\Framework;

class Document
{
    public static function addHeaderTag(array $element)
    {
        // TODO: use new class
        return false;
    }

    public static function rootUri()
    {
        return rtrim(\Mage::getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_WEB), '/');
    }
}
