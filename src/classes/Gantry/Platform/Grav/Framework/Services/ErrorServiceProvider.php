<?php
namespace Gantry\Framework\Services;

use Grav\Common\Grav;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ErrorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $grav = Grav::instance();

        $container['errors'] = $grav['errors'];
    }
}
