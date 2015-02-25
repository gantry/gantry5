<?php
namespace Gantry\Component\Stylesheet;

interface CssCompilerInterface
{
    public function getVariables();

    public function resetCache();
}
