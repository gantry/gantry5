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

namespace Gantry\Component\Url;

class Url
{
    /**
     * UTF8 aware parse_url().
     *
     * @param  string $url
     * @param  bool   $queryArray
     * @return array|bool
     */
    public static function parse($url, $queryArray = false)
    {
        $encodedUrl = preg_replace_callback(
            '%[^:/@?&=#]+%usD',
            function ($matches) { return rawurlencode($matches[0]); },
            $url
        );

        // PHP versions below 5.4.7 have troubles with URLs without scheme, so lets help by fixing that.
        // TODO: This is not needed in PHP >= 5.4.7, but for now we need to test if the function works.
        if ('/' === $encodedUrl[0] && false !== strpos($encodedUrl, '://')) {
            $schemeless = true;

            // Fix the path so that parse_url() will not return false.
            $parts = parse_url('fake://fake.com' . $encodedUrl);

            // Remove the fake values.
            unset($parts['scheme'], $parts['host']);

        } else {
            $parts = parse_url($encodedUrl);
        }

        if (!$parts) {
            return false;
        }

        // PHP versions below 5.4.7 do not understand schemeless URLs starting with // either.
        if (isset($schemeless) && !isset($parts['host']) && 0 === strpos($encodedUrl, '//')) {
            // Path is stored in format: //[host]/[path], so let's fix it.
            list($parts['host'], $path) = explode('/', substr($parts['path'], 2), 2);
            $parts['path'] = "/{$path}";
        }

        foreach($parts as $name => $value) {
            $parts[$name] = rawurldecode($value);
        }

        // Return query string also as an array if requested.
        if ($queryArray) {
            $parts['vars'] = isset($parts['query']) ? static::parseQuery($parts['query']) : [];
        }

        return $parts;
    }

    /**
     * Parse query string and return array.
     *
     * @param $query
     * @return mixed
     */
    public static function parseQuery($query)
    {
        parse_str($query, $vars);

        return $vars;
    }

    /**
     * Build parsed URL array.
     *
     * @param array $parsed_url
     * @return string
     */
    public static function build(array $parsed_url)
    {
        // Build query string from variables if they are set.
        if (isset($parsed_url['vars'])) {
            $parsed_url['query'] = static::buildQuery($parsed_url['vars']);
        }

        // Build individual parts of the url.
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "{$pass}@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        $scheme   = $host && !$scheme ? '//' : $scheme;

        return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
    }

    /**
     * Build query string from variables.
     *
     * @param array $vars
     * @return null|string
     */
    public static function buildQuery(array $vars)
    {
        $list = [];
        foreach ($vars as $key => $var) {
            $list[] = $key . '=' . rawurlencode($var);
        }

        return $list ? implode('&', $list) : null;
    }
}
