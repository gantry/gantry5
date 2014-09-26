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

        $container['config'] = function ($c) {
            return Config::instance(JPATH_CACHE . '/gantry5/config.php', $c['theme.path']);
        };

        $container['site'] = function ($c) {
            return new Site;
        };

        $container['page'] = function ($c) {
            return new Page($c);
        };

        return $container;
    }
}
