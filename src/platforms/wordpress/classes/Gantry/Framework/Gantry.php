<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Debugger;
use Pimple\ServiceProviderInterface;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;
use Timber\Timber;

/**
 * Class Gantry
 * @package Gantry\Framework
 */
class Gantry extends Base\Gantry
{
    /**
     * @return boolean
     */
    public function admin()
    {
        return \is_admin();
    }

    /**
     * @param string $location
     * @param bool   $force
     * @return array
     */
    public function styles($location = 'head', $force = false)
    {
        // Do not display head, WordPress will take care of it (most of the time).
        return !$force && $location === 'head' ? Document::$wp_styles : parent::styles($location);
    }

    /**
     * @param string $location
     * @param bool $force
     * @return array
     */
    public function scripts($location = 'head', $force = false)
    {
        // Do not display head and footer, WordPress will take care of it (most of the time).
        return !$force && in_array($location, ['head', 'footer']) ? Document::$wp_scripts[$location] : parent::scripts($location);
    }

    /**
     * @return static
     * @throws \LogicException
     */
    protected static function init()
    {
        // Make sure that Timber plugin is new enough or not installed.
        if (class_exists('Timber', false) && empty(\Timber::$version)) {
            $action = 'deactivate';
            $slug = 'timber-library/timber.php';
            throw new \LogicException('<strong>Timber Plugin</strong> is too old for <strong>Gantry 5</strong> and it is no longer needed. Click <a href="' . \wp_nonce_url(\add_query_arg(['action' => $action, 'plugin' => $slug], \admin_url( 'plugins.php')), 'deactivate-plugin_' . $slug) . '"><strong>here</strong></a> to deactivate it.');
        }

        $container = parent::init();

        if (class_exists('TimberHelper')) {
            // Using Timber plugin.
            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage('Using Timber Plugin v' . Timber::$version);
            }
        } else {
            // Using composer version of Timber; Initialize it.
            new Timber;
            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage('Using Timber Library v' . Timber::$version);
            }
        }

        $lookup = $container['loader']->getPrefixesPsr4()['Gantry\\'];
        $iterator = new \FilesystemIterator($lookup[0] . '/WordPress/Integration', \FilesystemIterator::SKIP_DOTS & \FilesystemIterator::UNIX_PATHS);

        /** @var \FilesystemIterator $file */
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                continue;
            }
            $class = "Gantry\\WordPress\\Integration\\{$file->getBasename()}\\{$file->getBasename()}";
            if (class_exists($class) && call_user_func([$class, 'enabled'])) {
                $integration = new $class;
                if ($integration instanceof ServiceProviderInterface) {
                    $container->register($integration);
                }
                if ($integration instanceof EventSubscriberInterface) {
                    $container['events']->addSubscriber($integration);
                }
            }
        }

        return $container;
    }

    /**
     * @return array
     */
    protected function loadGlobal()
    {
        return (array) \get_option('gantry5_plugin');
    }
}
