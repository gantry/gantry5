<?php
namespace Gantry\Component\Stylesheet;

abstract class CssCompiler implements CssCompilerInterface
{
    protected $type;

    protected $name;

    protected $debug = false;

    protected $variables;

    public function setVariables(array $variables)
    {
        $this->variables = array_filter($variables);

        return $this;
    }

    public function getVariables()
    {
        return $this->variables;
    }
}
