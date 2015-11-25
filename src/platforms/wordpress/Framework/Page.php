<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

class Page extends Base\Page
{
    public function url(array $args = [])
    {
        return home_url(add_query_arg($args, $GLOBALS['wp']->request));
    }

    public function htmlAttributes()
    {
        $site = Gantry::instance()['site'];

        $dir = [];
        if(function_exists('is_rtl') && is_rtl()) {
            $dir['dir'] = 'rtl';
        }

        $attributes = [
                'lang' => (string) $site->language,
              ]
              + (array) $dir
              + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes($attributes = [])
    {
        // TODO: we might need something like
        // class="{{body_class}}" data-template="{{ twigTemplate|default('base.twig') }}"

        $body_classes = apply_filters('gantry5_body_classes', [
                'site',
                'outline-' . Gantry::instance()['configuration'],
            ]);

        (is_rtl()) ? $body_classes[] = 'dir-rtl' : $body_classes[] = 'dir-ltr';
        $wp_body_class = get_body_class($body_classes);

        if(is_array($wp_body_class) && !empty($wp_body_class)) {
            $attributes['class'] = array_merge_recursive($attributes['class'], $wp_body_class);
        }

        return $this->getAttributes((array) $this->config->get('page.body.attribs'), $attributes);
    }
}
