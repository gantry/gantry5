<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Services;

use Gantry\Component\Filesystem\Streams;
use Gantry\Debugger;
use Gantry\Framework\Base\Platform;
use Gantry\Framework\Gantry;
use Pimple\Container;
use RocketTheme\Toolbox\DI\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class StreamsServiceProvider
 * @package Gantry\Framework\Services
 */
class StreamsServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $gantry
     */
    public function register(Container $gantry)
    {
        $gantry['locator'] = static function() {
            return new UniformResourceLocator(GANTRY5_ROOT);
        };

        $gantry['streams'] = static function(Gantry $gantry) {
            /** @var Platform $platform */
            $platform = $gantry['platform'];

            $schemes = (array) $platform->init()->get('streams');

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $streams = new Streams($locator);
            $streams->add($schemes);

            if (\GANTRY_DEBUGGER) {
                Debugger::setLocator($locator);
            }

            return $streams;
        };
    }
}
