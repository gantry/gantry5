<?php
namespace Gantry\Framework;

class Page
{
    protected $page;

    public function __construct($page)
    {
        $this->page = $page;
    }

    public function getChildHtml($position)
    {
        return $this->page->getChildHtml($position);
    }

    public function getLang()
    {
        return $this->page->getLang();
    }

    public function getBodyClass()
    {
        return $this->page->getBodyClass();
    }

    public function getAbsoluteFooter()
    {
        return $this->page->getAbsoluteFooter();
    }
}
