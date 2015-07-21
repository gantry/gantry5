<?php
namespace Gantry\Framework;

class Page extends Base\Page
{
    public function htmlAttributes()
    {
        $site = Gantry::instance()['site'];
        $attributes = [
                'lang' => (string) $site->language
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes($attributes = [])
    {
        // TODO: we might need something like
        // class="{{body_class}}" data-template="{{ twigTemplate|default('base.twig') }}"

        $wp_body_class = get_body_class();

        if(is_array($wp_body_class) && !empty($wp_body_class)) {
            $attributes['class'] = array_merge_recursive($attributes['class'], $wp_body_class);
        }

        return $this->getAttributes((array) $this->config->get('page.body'), $attributes);
    }
}
