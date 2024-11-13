<?php

/**
 * @package     Gantry 5
 *
 * @copyright   (C) 2007 - 2022 Flygcert FZE. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Gantry\Plugin\Gantry5\Preset\Extension\Preset;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

return new class implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container): Preset {
                $config     = (array) PluginHelper::getPlugin('system', 'gantry5');
                $dispatcher = $container->get(DispatcherInterface::class);

                $plugin = new Preset($dispatcher, $config);
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));

                return $plugin;
            }
        );
    }
};
