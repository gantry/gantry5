<?php
defined('STANDALONE_ROOT') or die;

define('GANTRYADMIN_PATH', STANDALONE_ROOT . '/admin');

$gantry['router'] = function ($c) {
    return new Gantry\Admin\Router($c);
};

$gantry['router']->dispatch();
