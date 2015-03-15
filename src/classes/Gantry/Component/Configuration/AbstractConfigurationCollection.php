<?php
namespace Gantry\Component\Configuration;

use Gantry\Component\Collection\Collection;
use RocketTheme\Toolbox\DI\Container;

abstract class AbstractConfigurationCollection extends Collection
{
    /**
     * @var Container
     */
    protected $container;

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
        foreach ($this->items as $key => $item) {
            if (substr($key, 0, 1) !== '_') {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function user()
    {
        foreach ($this->items as $key => $item) {
            if (substr($key, 0, 1) === '_') {
                unset($this->items[$key]);
            }
        }

        return $this;
    }
}
