<?php
namespace Gantry\Framework\Base;

use RocketTheme\Toolbox\File\JsonFile;

abstract class Theme
{
    use ThemeTrait;

    public $name;
    public $url;
    public $path;
    public $layout;

    public function __construct($path, $name = '')
    {
        if (!is_dir($path)) {
            throw new \LogicException('Theme not found!');
        }

        $this->path = $path;
        $this->name = $name ? $name : basename($path);
        $this->init();
    }

    abstract public function render($file, array $context = array());
}
