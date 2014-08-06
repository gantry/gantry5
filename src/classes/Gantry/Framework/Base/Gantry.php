<?php
namespace Gantry\Framework\Base;

use Gantry\Component\DI\Container;

class Gantry extends Container
{
    /**
     * @var static
     */
    protected static $instance;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = static::load();
        }

        return self::$instance;
    }

    protected static function load()
    {
        $instance = new Container();

        return $instance;
    }
}
