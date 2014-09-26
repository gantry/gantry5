<?php
namespace Gantry\Framework;

class Page extends Base\Page
{
    protected $config;
    protected $page;

    public function __construct($container, $page)
    {
        $this->config = $container['config'];
        $this->page = $page;
    }

    public function htmlAttributes()
    {
        $attributes = [
                'xml:lang' => $this->getLang(),
                'lang' => $this->getLang()
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes()
    {
        $attributes = (array) $this->config->get('page.body', []);
        if (!empty($attributes['class'])) {
            $attributes['class'] = array_merge((array) $attributes['class'], explode(' ', $this->getBodyClass()));
        } else {
            $attributes['class'] = explode(' ', $this->getBodyClass());
        }

        return $this->getAttributes(array_unique($attributes));
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
