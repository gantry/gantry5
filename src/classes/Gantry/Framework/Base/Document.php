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

namespace Gantry\Framework\Base;

use Gantry\Component\Gantry\GantryTrait;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Document
{
    use GantryTrait;

    public static function addHeaderTag(array $element)
    {
        return false;
    }

    public static function rootUri()
    {
        return '';
    }

    /**
     * Return URL to the resource.
     *
     * @example {{ url('theme://images/logo.png')|default('http://www.placehold.it/150x100/f4f4f4') }}
     *
     * @param  string $url    Resource to be located.
     * @param  bool $domain     True to include domain name.
     * @return string|null      Returns url to the resource or null if resource was not found.
     */
    public static function url($url, $domain = false)
    {
        if (!$url) {
            return null;
        }

        if ($url[0] == '/') {
            // Absolute path in our server, nothing to do.
            // TODO: add support to include domain..
            return $url;

        } elseif (strpos($url, '://') !== false) {
            // Resolve stream to a relative path.
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            try {
                // Remove fragment part before resolving location of the file.
                $parts = explode('#', $url, 2);
                $uri = array_shift($parts);
                $fragment = array_shift($parts);

                // Attempt to find our resource.
                $path = $locator->findResource($uri, false);
                if ($path) {
                    $timestamp = sprintf('%x', filemtime(GANTRY5_ROOT . '/' . $path));
                    $url = "{$path}?{$timestamp}" . ($fragment ? "#{$fragment}" : '');
                } else {
                    $url = null;
                }
            } catch (\Exception $e) {
                // Scheme did not exist; assume that we had valid scheme (like http) so no modification is needed.
                return $url;
            }
        }

        // TODO: add support to include domain..
        return $url ? rtrim(static::rootUri(), '/') . '/' . $url : null;
    }
}
