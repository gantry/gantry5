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

namespace Gantry\Framework\Base;

abstract class Page
{
    protected $container;
    protected $config;

    public function __construct($container)
    {
        $this->container = $container;
        $this->config = $container['config'];
    }

    public function doctype()
    {
        return $this->config->get('page.doctype', 'html');
    }

    abstract public function url(array $args = []);

    public function preset()
    {
        /** @var Theme $theme */
        $theme = $this->container['theme'];
        return 'g-' . preg_replace('/[^a-z0-9-]/', '', $theme->type());
    }

    public function htmlAttributes()
    {
        return $this->getAttributes($this->config->get('page.html'));
    }

    public function bodyAttributes($attributes = [])
    {
        return $this->getAttributes($this->config->get('page.body.attribs'), $attributes);
    }

    protected function getAttributes($params, $extra = [])
    {
        $params = array_merge_recursive($params, $extra);

        $list = [];
        foreach ($params as $param => $value) {
            if (!$value) { continue; }
            if (!is_array($value) || !count(array_filter($value, 'is_array'))) {
                $value = array_filter(array_unique((array) $value));
                $list[] = $param . '="' . implode(' ', $value) . '"';
            } else {
                $values = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($value));
                foreach ($values as $iparam => $ivalue) {
                    $ivalue = array_filter(array_unique((array) $ivalue));
                    $list[] = $iparam . '="' . implode(' ', $ivalue) . '"';
                }
            }

        }

        return $list ? ' ' . implode(' ', $list) : '';
    }
}
