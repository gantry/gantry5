<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Class Page
 * @package Gantry\Framework
 */
class Page extends Base\Page
{
    /** @var bool */
    public $home;

    /** @var string */
    public $outline;

    /** @var string */
    public $language;

    /** @var string */
    public $direction;

    // Joomla specific properties.
    /** @var string */
    public $tmpl;

    /** @var string */
    public $option;

    /** @var string */
    public $view;

    /** @var string */
    public $layout;

    /** @var string */
    public $task;

    /** @var string */
    public $theme;

    /** @var string */
    public $baseUrl;

    /** @var string */
    public $sitename;

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var string */
    public $class;

    /** @var string */
    public $printing;

    /** @var int */
    public $itemid;

    /**
     * Page constructor.
     * @param Gantry $container
     * @throws \Exception
     */
    public function __construct($container)
    {
        parent::__construct($container);

        $gantry = Gantry::instance();

        /** @var CMSApplication $app */
        $app   = Factory::getApplication();
        $input = $app->getInput();

        $this->tmpl     = $input->getCmd('tmpl', '');
        $this->option   = $input->getCmd('option', '');
        $this->view     = $input->getCmd('view', '');
        $this->layout   = $input->getCmd('layout', '');
        $this->task     = $input->getCmd('task', '');
        $this->itemid   = $input->getInt('Itemid', '');
        $this->printing = $input->getCmd('print', '');

        $this->class = '';

        if ($this->itemid) {
            $menu     = $app->getMenu();
            $menuItem = $menu ? $menu->getActive() : null;

            if ($menuItem && $menuItem->id) {
                $this->home  = (bool) $menuItem->home;
                $this->class = $menuItem->getParams()->get('pageclass_sfx', '');
            }
        }

        $templateParams = $app->getTemplate(true);

        $this->outline  = $gantry['configuration'];
        $this->sitename = $app->get('sitename');
        $this->theme    = $templateParams->template;
        $this->baseUrl  = Uri::base(true);

        // Document doesn't exist in error page if modern routing is being used.
        $document = $container['platform']->document ?? $app->getDocument();

        if ($document) {
            $this->title = $document->title;
            $this->description = $document->description;

            // Document has lower case language code, which causes issues with some JS scripts (Snipcart). Use tag instead.
            $code     = \explode('-', $document->getLanguage(), 2);
            $language = \array_shift($code);
            $country  = \strtoupper(\array_shift($code));

            $this->language  = $language . ($country ? '-' . $country : '');
            $this->direction = $document->direction;
        }
    }

    /**
     * @param array $args
     * @return string
     */
    public function url(array $args = []): string
    {
        $url = Uri::getInstance();

        foreach ($args as $key => $val) {
            $url->setVar($key, $val);
        }

        return $url->toString();
    }

    /**
     * @return string
     */
    public function htmlAttributes(): string
    {
        $attributes = [
                'lang' => $this->language,
                'dir'  => $this->direction
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    /**
     * @param ?array $attributes
     * @return string
     */
    public function bodyAttributes($attributes = []): string
    {
        if ($this->tmpl === 'component') {
            $classes = ['contentpane', 'modal'];
        } else {
            $classes = ['site', $this->option, "view-{$this->view}"];
            $classes[] = $this->layout ? 'layout-' . $this->layout : 'no-layout';
            $classes[] = $this->task ? 'task-' . $this->task : 'no-task';
        }

        if ($this->class) {
            $classes[] = $this->class;
        }

        if ($this->printing) {
            $classes[] = 'print-mode';
        }

        if ($this->itemid) {
            $classes[] = 'itemid-' . $this->itemid;
        }

        if ($this->outline) {
            $classes[] = 'outline-' . $this->outline;
        }

        $baseAttributes = (array) $this->config->get('page.body.attribs', []);


        $baseAttributes['class'] = !empty($baseAttributes['class'])
            ? \array_merge((array) $baseAttributes['class'], $classes) :
            $baseAttributes['class'] = $classes;

        return $this->getAttributes($baseAttributes, $attributes);
    }
}
