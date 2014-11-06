<?php
namespace Gantry\Component\Filesystem;

use Pimple\Container;
use RocketTheme\Toolbox\DI\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use RocketTheme\Toolbox\StreamWrapper\ReadOnlyStream;
use RocketTheme\Toolbox\StreamWrapper\Stream;

class StreamsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $gantry)
    {
        $sp = $this;

        $gantry['locator'] = function($c) use ($sp) {
            return new UniformResourceLocator(GANTRY5_ROOT);
        };
        $gantry['streams'] = function($c) use ($sp) {
            $schemes = (array) $c['setup']->get('streams');

            $streams = new Streams($c['locator']);
            $streams->add($schemes);

            return $streams;
        };
    }
}
