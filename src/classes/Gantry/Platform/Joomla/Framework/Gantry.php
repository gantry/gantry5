<?php
namespace Gantry\Framework;

class Gantry extends Base\Gantry
{
    /**
     * @throws \LogicException
     */
    protected static function load()
    {
        $container = parent::load();

        $container['site'] = function ($c) {
            return new Site;
        };

        $container['page'] = function ($c) {
            return new Page($c);
        };

        $container['menu'] = function ($c) {
            return new Menu;
        };

        return $container;
    }
}
