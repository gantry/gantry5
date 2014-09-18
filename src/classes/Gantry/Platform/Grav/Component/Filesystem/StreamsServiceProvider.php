<?php
namespace Gantry\Component\Filesystem;

use Grav\Common\Grav;
use Pimple\Container;
use RocketTheme\Toolbox\DI\ServiceProviderInterface;

class StreamsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $gantry)
    {
        $sp = $this;

        $gantry['locator'] = function() {
            return Grav::instance()['locator'];
        };

        $gantry['streams'] = function($c) use ($sp) {
            $schemes = $c['config']->get('streams.schemes');

            $streams = new Streams($c['locator']);
            $streams->add($schemes);

            return $streams;
        };
    }
}
