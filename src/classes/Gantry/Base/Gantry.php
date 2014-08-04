<?php
namespace Gantry\Base;

abstract class Gantry
{
    /**
     * @var static
     */
    protected static $instance;

    /**
     * @var Config
     */
    protected $config;

    protected $site;
    protected $theme;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    abstract function initialize(\Gantry\Theme\Theme $theme);

    /**
     * @return Config
     */
    public function config()
    {
        return $this->config;
    }

    public function site()
    {
        return $this->site;
    }

    public function theme()
    {
        return $this->theme;
    }
}
