<?php
namespace Gantry\Component\File;

use RocketTheme\Toolbox\File\YamlFile;

class CompiledYamlFile extends YamlFile
{
    protected static $cachePath;

    use CompiledFile;
}
