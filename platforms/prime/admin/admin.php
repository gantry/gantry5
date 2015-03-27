<?php
defined('PRIME_ROOT') or die;

define('GANTRYADMIN_PATH', PRIME_ROOT . '/admin');

$gantry['router'] = function ($c) {
    return new Gantry\Admin\Router($c);
};

$gantry['router']->dispatch();
