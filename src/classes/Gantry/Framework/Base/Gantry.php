<?php
namespace Gantry\Framework\Base;

use Gantry\Component\Layout\LayoutReader;
use RocketTheme\Toolbox\DI\Container;
use Gantry\Component\Filesystem\StreamsServiceProvider;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Gantry extends Container
{
    /**
     * @var static
     */
    protected static $instance;
    protected $wrapper;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = static::load();
        }

        return self::$instance;
    }

    public function route($route)
    {
        $routes = $this->offsetGet('routes');
        if (!isset($routes[$route])) {
            throw new \InvalidArgumentException(sprintf('Invalid route: %s', $route));
        }

        return $this->offsetGet('base_url') . $routes[$route];
    }

    public function wrapper($value = null)
    {
        if ($value !== null ) {
            $this->wrapper = $value;
        }

        return $this->wrapper;
    }

    protected static function load()
    {
        $instance = new static();

        $instance->register(new StreamsServiceProvider);

        $instance['layout'] = function ($c) {
            /** @var UniformResourceLocator $locator */
            $locator = $c['locator'];

            // Include Gantry specific things to the context.
            // $file = JsonFile::instance($locator('theme://layouts/test.json'));
            // return $file->content();

            return LayoutReader::read($locator('theme://layouts/test.yaml'));
        };

        return $instance;
    }
}
