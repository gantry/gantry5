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

namespace Gantry\Component\Filesystem;

use RocketTheme\Toolbox\DI\ServiceProviderInterface;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use RocketTheme\Toolbox\StreamWrapper\ReadOnlyStream;
use RocketTheme\Toolbox\StreamWrapper\Stream;

class Streams
{
    /**
     * @var array
     */
    protected $schemes = [];

    /**
     * @var UniformResourceLocator
     */
    protected $locator;

    public function __construct(UniformResourceLocator $locator = null)
    {
        if ($locator) {
            $this->setLocator($locator);
        }
    }

    /**
     * @param UniformResourceLocator $locator
     */
    public function setLocator(UniformResourceLocator $locator)
    {
        $this->locator = $locator;

        // Set locator to both streams.
        Stream::setLocator($locator);
        ReadOnlyStream::setLocator($locator);
    }

    /**
     * @return UniformResourceLocator
     */
    public function getLocator()
    {
        return $this->locator;
    }

    public function add(array $schemes)
    {
        foreach ($schemes as $scheme => $config) {
            if (isset($config['paths'])) {
                $this->locator->addPath($scheme, '', $config['paths']);
            }
            if (isset($config['prefixes'])) {
                foreach ($config['prefixes'] as $prefix => $paths) {
                    $this->locator->addPath($scheme, $prefix, $paths);
                }
            }
            $type = !empty($config['type']) ? $config['type'] : 'ReadOnlyStream';
            if ($type[0] != '\\') {
                $type = '\\Rockettheme\\Toolbox\\StreamWrapper\\' . $type;
            }
            $this->schemes[$scheme] = $type;
        }
    }

    public function register()
    {
        $registered = stream_get_wrappers();

        foreach ($this->schemes as $scheme => $type) {
            if (in_array($scheme, $registered)) {
                stream_wrapper_unregister($scheme);
            }

            if (!stream_wrapper_register($scheme, $type)) {
                throw new \InvalidArgumentException("Stream '{$type}' could not be initialized.");
            }
        }
    }
}
