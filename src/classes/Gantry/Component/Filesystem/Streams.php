<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Filesystem;

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
     * @var array
     */
    protected $registered;

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
            $force = !empty($config['force']);

            if (isset($config['paths'])) {
                $this->locator->addPath($scheme, '', $config['paths'], false, $force);
            }
            if (isset($config['prefixes'])) {
                foreach ($config['prefixes'] as $prefix => $paths) {
                    $this->locator->addPath($scheme, $prefix, $paths, false, $force);
                }
            }
            $type = !empty($config['type']) ? $config['type'] : 'ReadOnlyStream';
            if ($type[0] != '\\') {
                $type = '\\Rockettheme\\Toolbox\\StreamWrapper\\' . $type;
            }
            $this->schemes[$scheme] = $type;

            if (isset($this->registered)) {
                $this->doRegister($scheme, $type);
            }
        }
    }

    public function register()
    {
        $this->registered = stream_get_wrappers();

        foreach ($this->schemes as $scheme => $type) {
            $this->doRegister($scheme, $type);
        }
    }

    protected function doRegister($scheme, $type)
    {
        if (in_array($scheme, $this->registered)) {
            stream_wrapper_unregister($scheme);
        }

        if (!stream_wrapper_register($scheme, $type)) {
            throw new \InvalidArgumentException("Stream '{$type}' could not be initialized.");
        }
    }
}
