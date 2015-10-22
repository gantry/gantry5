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

    public function bodyAttributes($attributes = [])
    {
        $classes = ['nojs', 'notouch', 'section-{SCRIPT_NAME}', '{S_CONTENT_DIRECTION}', '{BODY_CLASS}'];

        $baseAttributes = (array) $this->config->get('page.body.attribs', []);
        $baseAttributes['id'] = 'phpbb';

        if (!empty($baseAttributes['class'])) {
            $baseAttributes['class'] = array_merge((array) $baseAttributes['class'], $classes);
        } else {
            $baseAttributes['class'] = $classes;
        }

        return $this->getAttributes($baseAttributes, $attributes);
    }
}
