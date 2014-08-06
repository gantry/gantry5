<?php
namespace Gantry\Framework;

class Site
{
    public function __construct()
    {
        $document = \JFactory::getDocument();

        $this->theme = $document->template;
        $this->url = $document->baseurl;
        $this->title = $document->title;
        $this->description = $document->description;
    }
}
