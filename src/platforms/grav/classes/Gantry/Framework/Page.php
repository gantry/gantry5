<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Url\Url;
use Grav\Common\Grav;
use Grav\Common\Language\Language;
use Grav\Common\Language\LanguageCodes;
use Grav\Common\Page\Interfaces\PageInterface;

/**
 * Class Page
 * @package Gantry\Framework
 */
class Page extends Base\Page
{
    /** @var string */
    public $theme;
    /** @var string */
    public $baseUrl;
    /** @var string */
    public $title;
    /** @var string */
    public $description;
    /** @var string */
    public $outline;
    /** @var string */
    public $language;
    /** @var string */
    public $direction;

    /**
     * Page constructor.
     * @param Gantry $container
     */
    public function __construct($container)
    {
        parent::__construct($container);

        $grav = Grav::instance();

        /** @var Language $language */
        $language = $grav['language'];

        $this->outline = $container['configuration'];
        $this->language = $language->getLanguage() ?: 'en';
        $this->direction = LanguageCodes::getOrientation($this->language);
    }

    /**
     * @param array $args
     * @return string
     */
    public function url(array $args = [])
    {
        $grav = Grav::instance();
        $url = $grav['uri']->url;

        $parts = Url::parse($url, true);
        $parts['vars'] = array_replace($parts['vars'], $args);

        return Url::build($parts);
    }

    /**
     * @return string
     */
    public function htmlAttributes()
    {
        $attributes = [
                'lang' => $this->language,
                'dir' => $this->direction
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    /**
     * @param array $attributes
     * @return string
     */
    public function bodyAttributes($attributes = [])
    {
        $grav = Grav::instance();

        /** @var PageInterface $page */
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
