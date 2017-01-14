<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Url\Url;
use Grav\Common\Grav;
use Grav\Common\Language\LanguageCodes;

class Page extends Base\Page
{
    public $theme;
    public $baseUrl;
    public $title;
    public $description;

    public $outline;
    public $language;
    public $direction;

    public function __construct($container)
    {
        parent::__construct($container);

        $grav = Grav::instance();

        $this->outline = $container['configuration'];
        $this->language = $grav['language']->getLanguage() ?: 'en';
        $this->direction = LanguageCodes::getOrientation($this->language);
    }

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
                'lang' => $this->language,
                'dir' => $this->direction
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes($attributes = [])
    {
        $grav = Grav::instance();
        $page = $grav['page'];

        $classes = [
            'site',
            $page ? $page->template() : '',
            "dir-$this->direction",
            "outline-{$this->outline}",
        ];

        $header = $page->header();
        if (!empty($header->body_classes)) {
            $classes[] = $header->body_classes;
        }
        $baseAttributes = (array) $this->config->get('page.body.attribs', []);
        if (!empty($baseAttributes['class'])) {
            $baseAttributes['class'] = array_merge((array) $baseAttributes['class'], $classes);
        } else {
            $baseAttributes['class'] = $classes;
        }

        return $this->getAttributes($baseAttributes, $attributes);
    }
}
