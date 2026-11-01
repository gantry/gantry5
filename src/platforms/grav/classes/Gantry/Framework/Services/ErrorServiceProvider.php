<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @maintenance Copyright (c) 2026 Matthew C. Murphy, https://MatthewxMurphy.com
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework\Services;

use Grav\Common\Grav;
use Grav\Common\User\Interfaces\UserInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class ErrorServiceProvider
 * @package Gantry\Framework\Services
 */
class ErrorServiceProvider implements ServiceProviderInterface
{
    /**
     * Gantry error services require an authenticated Grav plugin-settings administrator.
     *
     * @return bool
     */
    public static function allowed()
    {
        try {
            $grav = Grav::instance();
            if (isset($grav['admin'])) {
                $admin = $grav['admin'];
                if (!is_object($admin)) {
                    return false;
                }
                $user = $admin->user;
            } else {
                $user = isset($grav['user']) ? $grav['user'] : null;
            }

            return $user instanceof UserInterface && $user->authenticated
                && $user->authorize('admin.login') && $user->authorize('admin.plugins');
        } catch (\Throwable $e) {
            // Authentication may not be available during early or failed Grav bootstrap.
            return false;
        }
    }

    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        if (!static::allowed()) {
            return;
        }

        $grav = Grav::instance();

        $container['errors'] = $grav['errors'];
    }
}
