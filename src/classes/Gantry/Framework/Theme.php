<?php
namespace Gantry\Framework;

class Theme extends Base\Theme
{
    public function __construct($path, $name = '')
    {
        if (!is_dir($path)) {
            throw new \LogicException('Theme not found!');
        }

        $this->path = $path;
        $this->name = $name ? $name : basename($path);
    }

    public function render($file, array $context = array()) {}
}
