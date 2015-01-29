<?php
namespace Gantry\Component\Stylesheet;

abstract class CssCompiler implements CssCompilerInterface
{
    protected $type;

    protected $name;

    protected $debug = false;

    public function getVariables()
    {
        return [];
    }
}
