<?php
namespace Gantry\Component\Configuration;

use Gantry\Component\Collection\Collection;
use RocketTheme\Toolbox\DI\Container;

abstract class AbstractConfigurationCollection extends Collection
{
    /**
     * @var Container
     */
    public $container;

    public function __construct(Container $container, $items = [])
    {
        $this->container = $container;
        $this->items = $items;
    }

    /**
     * @param string $path
     * @return $this
     */
    abstract public function load($path = 'gantry-config://');

    public function all()
    {
        return $this;
    }

    public function system()
    {
        $this->items = array_values(array_filter($this->items, function($val) { return substr($val, 0, 1) === '_'; }));

        return $this;
    }

    public function user()
    {
        $this->items = array_values(array_filter($this->items, function($val) { return substr($val, 0, 1) !== '_'; }));

        return $this;
    }
}
