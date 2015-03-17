<?php
namespace Gantry\Framework;

class Gantry extends Base\Gantry
{
    public function styles()
    {
        return Document::$styles;
    }

    public function scripts($inFooter = false)
    {
        return Document::$scripts[$inFooter ? 'footer' : 'header'];
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

        return $container;
    }
}
