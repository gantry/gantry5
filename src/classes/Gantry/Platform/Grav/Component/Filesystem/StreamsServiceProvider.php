<?php
namespace Gantry\Component\Filesystem;

use Grav\Common\Grav;
use Pimple\Container;
use RocketTheme\Toolbox\DI\ServiceProviderInterface;

class StreamsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $gantry)
    {
        $gantry['locator'] = function() {
            return Grav::instance()['locator'];
        };
    }
}
