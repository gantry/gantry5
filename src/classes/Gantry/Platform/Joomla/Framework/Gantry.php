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
            $path = isset($c['theme.path']) ? $c['theme.path'] : GANTRYADMIN_PATH;

            return Config::instance(JPATH_CACHE . '/gantry5/config.php', $path);
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
