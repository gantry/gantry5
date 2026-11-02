<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework)
 * @currentDeveloper  Tiger12, LLC
 * @copyright Copyright (C) 2007 - 2022 Tiger12, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Admin;

/**
 * Pure allow-list for restricted module-editor admin JSON entry.
 *
 * @package Gantry\Component\Admin
 */
class ModuleEditorAccess
{
    /**
     * Resolve the effective HTTP method (post METHOD wins over override header).
     *
     * @param string $requestMethod
     * @param string $overrideHeader
     * @param string $postMethod
     * @return string
     */
    public static function effectiveMethod($requestMethod, $overrideHeader, $postMethod)
    {
        $method = strtoupper((string) $requestMethod);
        if ($method === '') {
            $method = 'GET';
        }

        if ($method === 'POST') {
            $postMethod = is_string($postMethod) ? trim($postMethod) : '';
            if ($postMethod !== '') {
                return strtoupper($postMethod);
            }

            $overrideHeader = is_string($overrideHeader) ? trim($overrideHeader) : '';
            if ($overrideHeader !== '') {
                return strtoupper($overrideHeader);
            }
        }

        return $method;
    }

    /**
     * Whether a restricted module-editor request is allowed.
     *
     * @param string $view
     * @param string $format
     * @param string $method Effective method (already resolved).
     * @return bool
     */
    public static function isAllowed($view, $format, $method)
    {
        if (strtolower((string) $format) !== 'json') {
            return false;
        }

        $method = strtoupper((string) $method);
        $raw = (string) $view;
        if (strpos($raw, '\\') !== false || strpos($raw, "\0") !== false) {
            return false;
        }
        $segments = preg_split('#/#', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($segments) || count($segments) === 0) {
            return false;
        }

        foreach ($segments as $segment) {
            if ($segment === ''
                || strpos($segment, '..') !== false
                || strpos($segment, '\\') !== false
                || strpos($segment, "\0") !== false
            ) {
                return false;
            }
        }

        $first = $segments[0];
        $allowed = ['particle', 'fontpicker', 'icons', 'filepicker'];
        if (!in_array($first, $allowed, true)) {
            return false;
        }

        if ($first === 'particle') {
            if ($method === 'GET') {
                return true;
            }
            if ($method === 'POST') {
                $count = count($segments);
                if ($count === 2) {
                    return true;
                }
                if ($count === 3 && $segments[2] === 'validate') {
                    return true;
                }
            }

            return false;
        }

        if ($first === 'fontpicker' || $first === 'icons') {
            return ($method === 'GET' || $method === 'POST');
        }

        // filepicker: GET/POST only; controller enforces path rules.
        if ($first === 'filepicker') {
            return ($method === 'GET' || $method === 'POST');
        }

        return false;
    }

    /**
     * Exception used when a restricted request is denied (HTTP 403).
     *
     * @param string $message
     * @return \Exception
     */
    public static function forbidden($message = 'Forbidden')
    {
        if (class_exists('Joomla\\CMS\\Access\\Exception\\NotAllowed')) {
            return new \Joomla\CMS\Access\Exception\NotAllowed($message, 403);
        }

        return new \RuntimeException($message, 403);
    }
}
