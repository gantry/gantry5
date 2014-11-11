<?php
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

    public function bodyAttributes()
    {
        $classes = ['site', $this->option, "view-{$this->view}"];
        $classes[] = $this->layout ? 'layout-' . $this->layout : 'no-layout';
        $classes[] = $this->task ? 'task-' . $this->task : 'no-task';
        if ($this->itemid) $classes[] = 'itemid-' . $this->itemid;

        $attributes = (array) $this->config->get('page.body', []);
        if (!empty($attributes['class'])) {
            $attributes['class'] = array_merge((array) $attributes['class'], $classes);
        } else {
            $attributes['class'] = $classes;
        }

        return $this->getAttributes(array_unique($attributes));
    }
}
