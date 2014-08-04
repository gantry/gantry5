<?php
namespace Gantry\Base;

abstract class Gantry
{
    protected static $instance;
    protected $theme;
    protected $site;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    abstract function initialize(\Gantry\Theme\Theme $theme);

    public function theme()
    {
        return $this->theme;
    }

    public function site()
    {
        return $this->site;
    }
}
