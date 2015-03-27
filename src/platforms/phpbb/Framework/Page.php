<?php
namespace Gantry\Framework;

class Page extends Base\Page
{
    public function htmlAttributes()
    {
        $attributes = [
                'dir' => '{S_CONTENT_DIRECTION}',
                'lang' => '{S_USER_LANG}'
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes()
    {
        $classes = ['nojs', 'notouch', 'section-{SCRIPT_NAME}', '{S_CONTENT_DIRECTION}', '{BODY_CLASS}'];

        $attributes = (array) $this->config->get('page.body', []);
        $attributes['id'] = 'phpbb';

        if (!empty($attributes['class'])) {
            $attributes['class'] = array_merge((array) $attributes['class'], $classes);
        } else {
            $attributes['class'] = $classes;
        }

        return $this->getAttributes(array_unique($attributes));
    }
}
