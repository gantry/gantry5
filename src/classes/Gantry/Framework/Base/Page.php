<?php
namespace Gantry\Framework\Base;

class Page
{
    protected $config;

    public function __construct($container)
    {
        $this->config = $container['config'];
    }

    public function doctype()
    {
        return $this->config->get('page.doctype');
    }

    public function htmlAttributes()
    {
        return $this->getAttributes($this->config->get('page.html'));
    }

    public function bodyAttributes()
    {
        return $this->getAttributes($this->config->get('page.body'));
    }

    protected function getAttributes($params)
    {
        $list = [];
        foreach ($params as $param => $value) {
            $value = array_unique((array) $value);
            $list[] = $param . '="' . implode(' ', $value) . '"';
        }

        return $list ? ' ' . implode(' ', $list) : '';
    }
}
