<?php
namespace Gantry\Framework;

use Grav\Common\Grav;
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
        $grav = Grav::instance();
        return rtrim($grav['base_url'], '/');
    }
}
