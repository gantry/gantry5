<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress\Integration\BuddyPress;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;

/**
 * Class BuddyPress
 * @package Gantry\WordPress\Integration
 */

class BuddyPress implements ServiceProviderInterface, EventSubscriberInterface
{
    /**
     * Enabler
     *
     * @return bool
     */
    public static function enabled() {
        // Required BuddyPress version
        $req_bp_version = '2.6';

        return in_array('buddypress/bp-loader.php', \apply_filters('active_plugins', \get_option('active_plugins')), true)
            && version_compare(BP_VERSION, $req_bp_version, '>');
    }


    /**
     * Register services to Gantry DI. Needed if you want to access something globally or from Twig template.
     *
     * Example: {{ gantry.buddypress.do_something() }}
     *
     * @param Container $gantry
     */
    public function register(Container $gantry)
    {
        $loader = $gantry['loader'];
        $loader->addClassMap(
            [
                'Gantry\\WordPress\\Assignments\\AssignmentsBuddyPress' => __DIR__ . '/Assignments.php'
            ]
        );
    }

    /**
     * Subscribe to Gantry events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'theme.init'        => ['onThemeInit', 0],
            'assignments.types' => ['onAssigmentsTypes', 0]
        ];
    }

    /**
     * Called from Theme::init()
     *
     * @param Event $event
     */
    public function onThemeInit(Event $event)
    {
        \add_filter('g5_assignments_page_context_array', ['Gantry\\WordPress\\Assignments\\AssignmentsBuddyPress', 'addPageContextItem']);
        \add_filter('g5_assignments_page_context_rules', ['Gantry\\WordPress\\Assignments\\AssignmentsBuddyPress', 'addPageContextConditionals'], 10, 2);
    }

    /**
     * Called from Attachments::types()
     *
     * @param Event $event
     */
    public function onAssigmentsTypes(Event $event)
    {
        $event->types[] = 'buddypress';
    }
}
