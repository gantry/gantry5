<?php
namespace Gantry\Framework;

class Gantry extends Base\Gantry
{
    public function styles()
    {
        return [];
    }

    public function scripts($inFooter = false)
    {
        if ($inFooter) {
            return Document::$scripts['footer'];
        }
        return [];
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

        $container['page'] = function ($c) {
            return new Page($c);
        };

        return $container;
    }
}
