<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

\defined('_JEXEC') or die;

use Gantry\Plugin\Quickicon\Gantry5\Extension\Gantry5;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = PluginHelper::getPlugin('quickicon', 'gantry5');

                $plugin = new Gantry5(
                    $container->get(DispatcherInterface::class),
                    Factory::getApplication()->getDocument(),
                    (array) $plugin
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
