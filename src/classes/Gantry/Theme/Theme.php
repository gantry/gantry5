<?php
namespace Gantry\Theme;

class Theme
{
    public $path;

    /**
     * @param string $path
     * @throws \LogicException
     */
    public function __construct($path)
    {
        if (!is_dir($path)) {
            throw new \LogicException('Theme not found!');
        }

        $this->path = $path;
    }
}
