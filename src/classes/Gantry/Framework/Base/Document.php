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
     * @param  bool $timestamp  True to append timestamp for the existing files.
     * @return string|null      Returns url to the resource or null if resource was not found.
     */
    public static function url($url, $domain = false, $timestamp = true)
    {
        if (!$url) {
            return null;
        }

        if ($url[0] == '/') {
            // Absolute path in our server.
            // TODO: add support to include domain..
            return $url;

        }

        // Remove fragment for stream / file lookup.
        $parts = explode('#', $url, 2);
        $uri = array_shift($parts);
        $fragment = array_shift($parts);

        // Remove parameters for stream / file lookup.
        $parts = explode('?', $uri, 2);
        $uri = array_shift($parts);
        $params = array_shift($parts);

        if (strpos($uri, '://') !== false) {
            // Resolve stream to a relative path.
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            try {
                // Attempt to find our resource.
                $uri = $locator->findResource($uri, false);
                if (!$uri) {
                    // Resource not found.
                    return null;
                }
            } catch (\Exception $e) {
                // Scheme did not exist; assume that we had valid scheme (like http) so no modification is needed.
                return $url;
            }
        }

        if ($timestamp) {
            // We want to add timestamp to the URL: do it only for local files.
            $realPath = realpath(GANTRY5_ROOT . '/' . $uri);
            if ($realPath) {
                $params = $params ? "?{$params}&" : '?';
                $params .= sprintf('%x', filemtime($realPath));
            }
        }

        // Add parameters back.
        $uri .= $params;

        // Add fragment back.
        if ($fragment) {
            $uri .= '#' . $fragment;
        }

        // TODO: add support to include domain..
        return rtrim(static::rootUri(), '/') . '/' . $uri;
    }
}
