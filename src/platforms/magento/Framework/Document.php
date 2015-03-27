<?php
namespace Gantry\Framework;

use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
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
