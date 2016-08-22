<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Url\Url;
use Grav\Common\Grav;

class Page extends Base\Page
{
    public $theme;
    public $baseUrl;
    public $title;
    public $description;
    public $direction;

    public function url(array $args = [])
    {
        $grav = Grav::instance();
        $url = $grav['uri']->url;

        $parts = Url::parse($url, true);
        $parts['vars'] = array_replace($parts['vars'], $args);

        return Url::build($parts);
    }

    public function htmlAttributes()
    {
        $attributes = [
                'lang' => 'en-GB',
                // TODO:
                'dir' => 'ltr'
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes($attributes = [])
    {
        $gantry = Gantry::instance();
        $grav = Grav::instance();
        $page = $grav['page'];

        $classes = [
            'site',
            $page ? $page->template() : '',
            // TODO:
            "dir-ltr",
            "outline-{$gantry['configuration']}",
        ];

        $baseAttributes = (array) $this->config->get('page.body.attribs', []);
        if (!empty($baseAttributes['class'])) {
            $baseAttributes['class'] = array_merge((array) $baseAttributes['class'], $classes);
        } else {
            $baseAttributes['class'] = $classes;
        }

        return $this->getAttributes($baseAttributes, $attributes);
    }
}
