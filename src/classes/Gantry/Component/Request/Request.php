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

namespace Gantry\Component\Request;

use Gantry\Framework\Base\Gantry;

class Request
{
    protected $method;

    public function getMethod()
    {
        if (!$this->method) {
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
            if ('POST' === $method) {
                $method = isset($_SERVER['X-HTTP-METHOD-OVERRIDE']) ? $_SERVER['X-HTTP-METHOD-OVERRIDE'] : $method;
                $method = isset($_POST['METHOD']) ? $_POST['METHOD'] : $method;
            }
            $this->method = strtoupper($method);
        }

        return $this->method;
    }

    /**
     * Get value by using dot notation for nested arrays/objects.
     *
     * @example $value = $this->get('this.is.my.nested.variable');
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $default    Default value (or null).
     * @param string  $separator  Separator, defaults to '.'
     * @return mixed  Value.
     */
    public function get($name = null, $default = null, $separator = '.')
    {
        if (!$name) {
            return $_POST;
        }
        $path = explode($separator, $name);
        $current = $_POST;
        foreach ($path as $field) {
            if (is_array($current) && isset($current[$field])) {
                $current = $current[$field];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * @param string $path
     * @return array|string
     */
    public function getArray($path = null)
    {
        $data = $this->get($path);
        return (array) $this->getChildren($data);
    }

    /**
     * @param $current
     * @return array|mixed
     * @internal
     */
    protected function getChildren(&$current)
    {
        if (!is_array($current)) {
            return $current;
        }
        $array = [];
        foreach ($current as $key => &$value) {
            if ($key === '_json') {
                $array += json_decode($value, true);
            } else {
                $array[$key] = $this->getChildren($value);
            }
        }

        return $array;
    }
}
