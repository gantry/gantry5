<?php
namespace Gantry\Framework;

use Grav\Common\Registry;

class Site
{
    public function __construct()
    {
        $config = Registry::get('Config');
        $uri = Registry::get('Uri');
        $this->theme = $config->get('system.theme');
        $this->url = $uri->rootUrl();
        $this->title = $config->get('site.title');
        $this->description = $config->get('site.description');
    }
}
