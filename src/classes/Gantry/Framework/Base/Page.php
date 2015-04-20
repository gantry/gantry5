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

class Page
{
    protected $config;

    public function __construct($container)
    {
        $this->config = $container['config'];
    }

    public function doctype()
    {
        return $this->config->get('page.doctype');
    }

    public function htmlAttributes()
    {
        return $this->getAttributes($this->config->get('page.html'));
    }

    public function bodyAttributes($attributes = [])
    {
        return $this->getAttributes($this->config->get('page.body'), $attributes);
    }

    protected function getAttributes($params, $extra = [])
    {
        $params = array_merge_recursive($params, $extra);

        $list = [];
        foreach ($params as $param => $value) {
            $value = array_filter(array_unique((array) $value));
            $list[] = $param . '="' . implode(' ', $value) . '"';
        }

        return $list ? ' ' . implode(' ', $list) : '';
    }
}
