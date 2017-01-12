<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Url\Url;

class Page extends Base\Page
{
    public $theme;
    public $baseUrl;
    public $title;
    public $description;
    public $language = 'en';
    public $direction = 'ltr';

    public function url(array $args = [])
    {
        $url = PRIME_URI;

        $parts = Url::parse($url, true);
        $parts['vars'] = array_replace($parts['vars'], $args);

        return Url::build($parts);
    }

    public function htmlAttributes()
    {
        $attributes = [
                'lang' => $this->language,
                'dir' => $this->direction
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes($attributes = [])
    {
        $gantry = Gantry::instance();
        $classes = [
            'site',
            "dir-{$this->direction}",
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
