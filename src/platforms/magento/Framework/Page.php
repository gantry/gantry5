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
                'lang' => $this->getLang()
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes($attributes = [])
    {
        $baseAttributes = (array) $this->config->get('page.body', []);
        if (!empty($baseAttributes['class'])) {
            $baseAttributes['class'] = array_merge((array) $baseAttributes['class'], explode(' ', $this->getBodyClass()));
        } else {
            $baseAttributes['class'] = explode(' ', $this->getBodyClass());
        }

        return $this->getAttributes($baseAttributes, $attributes);
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
