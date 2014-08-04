<?php
namespace Gantry\Theme;

class Theme
{
    protected $folder;
    protected $config;
    protected $positions;

    /**
     * @param $folder
     * @throws \LogicException
     */
    public function __construct($folder)
    {
        if (!is_dir($folder)) {
            throw new \LogicException('Theme not found!');
        }

        $this->folder = $folder;
    }
}
