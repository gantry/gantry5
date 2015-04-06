<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Services;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Streams;
use Pimple\Container;
use RocketTheme\Toolbox\DI\ServiceProviderInterface;
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
            $schemes = (array) $c['platform']->get('streams');

            /** @var UniformResourceLocator $locator */
            $locator = $c['locator'];

            $streams = new Streams($locator);
            $streams->add($schemes);

            CompiledYamlFile::setCachePath($locator->findResource('gantry-cache://compiled/yaml', true, true));

            return $streams;
        };
    }
}
