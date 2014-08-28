<?php
namespace Gantry\Framework\Base;

use RocketTheme\Toolbox\DI\Container;
use Gantry\Component\Filesystem\StreamsServiceProvider;

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

        $instance->register(new StreamsServiceProvider);

        return $instance;
    }
}
