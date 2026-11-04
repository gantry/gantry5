<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework\Services;

use Grav\Common\Grav;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class ErrorServiceProvider
 * @package Gantry\Framework\Services
 */
class ErrorServiceProvider implements ServiceProviderInterface
{
    /**
     * Detailed error pages are only allowed for logged-in Grav administrators.
     *
     * @return bool
     */
    public static function allowed()
    {
        try {
            $grav = Grav::instance();
            if (!isset($grav['user'])) {
                return false;
            }
            $user = $grav['user'];

            return $user && !empty($user->authenticated)
                && ($user->authorize('admin.super') || $user->authorize('admin.login'));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $grav = Grav::instance();

        $container['errors'] = $grav['errors'];
    }
}
