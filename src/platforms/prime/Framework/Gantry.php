<?php
namespace Gantry\Framework;

use Gantry\Component\Config\Config;

class Gantry extends Base\Gantry
{
    /**
     * @return boolean
     */
    public function debug()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function admin()
    {
        return true;
    }

    /**
     * @throws \LogicException
     */
    protected static function load()
    {
        $container = parent::load();

        $container['site'] = function ($c) {
            return new Site;
        };

        $container['menu'] = function ($c) {
            return new Menu;
        };

        $container['global'] = function ($c) {
            return new Config([]);
        };

        return $container;
    }
}
