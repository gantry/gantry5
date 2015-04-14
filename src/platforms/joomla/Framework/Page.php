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
    public $theme;
    public $baseUrl;
    public $title;
    public $description;

    public function __construct($container)
    {
        parent::__construct($container);

        $app = \JFactory::getApplication();
        $document = \JFactory::getDocument();
        $input = $app->input;

        $this->option   = $input->getCmd('option', '');
        $this->view     = $input->getCmd('view', '');
        $this->layout   = $input->getCmd('layout', '');
        $this->task     = $input->getCmd('task', '');
        $this->itemid   = $input->getCmd('Itemid', '');

        $this->sitename = $app->get('sitename');
        $this->theme = $document->template;
        $this->baseUrl = $document->baseurl;
        $this->title = $document->title;
        $this->description = $document->description;
        $this->language = $document->language;
        $this->direction = $document->direction;
    }

    public function htmlAttributes()
    {
        $attributes = [
                'xml:lang' => $this->language,
                'lang' => $this->language,
                'dir' => $this->direction
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes($attributes = [])
    {
        $classes = ['site', $this->option, "view-{$this->view}"];
        $classes[] = $this->layout ? 'layout-' . $this->layout : 'no-layout';
        $classes[] = $this->task ? 'task-' . $this->task : 'no-task';
        if ($this->itemid) $classes[] = 'itemid-' . $this->itemid;

        $baseAttributes = (array) $this->config->get('page.body', []);
        if (!empty($baseAttributes['class'])) {
            $baseAttributes['class'] = array_merge((array) $baseAttributes['class'], $classes);
        } else {
            $baseAttributes['class'] = $classes;
        }

        return $this->getAttributes($baseAttributes, $attributes);
    }
}
