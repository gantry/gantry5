<?php
namespace Gantry\Framework;

use Gantry\Component\Translator\TranslatorInterface;

class Translator implements TranslatorInterface
{
    public function translate($string)
    {
        return $string;
    }
}
