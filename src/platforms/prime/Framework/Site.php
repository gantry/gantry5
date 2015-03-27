<?php
namespace Gantry\Framework;

class Site
{
    public function __construct()
    {
        $this->url = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $this->title = 'Title';
        $this->description = 'Description';
    }
}
