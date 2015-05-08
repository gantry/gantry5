<?php
namespace Gantry\Framework;

use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
{
    public static function rootUri()
    {
        return PRIME_URI;
    }
}
