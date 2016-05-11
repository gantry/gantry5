<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Outline;

use Gantry\Component\Collection\Collection;
use RocketTheme\Toolbox\DI\Container;

abstract class AbstractOutlineCollection extends Collection
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

    public function name($id)
    {
        return isset($this->items[$id]) ? $this->items[$id] : null;
    }

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
            if (substr($key, 0, 1) === '_' || $key == 'default') {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function filter(array $include = null)
    {
        if ($include !== null) {
            foreach ($this->items as $key => $item) {
                if (!in_array($key, $include)) {
                    unset($this->items[$key]);
                }
            }
        }

        return $this;
    }
}
