<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Grav\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Uri;

class AssignmentsPage implements AssignmentsInterface
{
    public $type = 'page';
    public $priority = 3;

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        $grav = Grav::instance();

        /** @var Uri $uri */
        $uri = $grav['uri'];

        $route = trim($uri->path(), '/');
        $home = trim($grav['config']->get('system.home.alias', '/home'), '/');
        $rules[$route ?: $home] = $this->priority;

        return [$rules];
    }

    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function listRules($configuration)
    {
        // Get label and items for each menu
        $list = [
                'label' => 'Pages',
                'items' => $this->getItems()
        ];

        return [$list];
    }

    protected function getItems()
    {
        $grav = Grav::instance();

        /** @var Pages $pages */
        $pages = $grav['pages'];

        // Initialize pages; in Grav 1.7 admin, pages are not initialized by default.
        if (method_exists($pages, 'enablePages')) {
            $pages->enablePages();
        }

        $pages = $pages->all()->routable();

        $items = [];

        /** @var Page $page */
        foreach ($pages as $page) {
            $route = trim($page->rawRoute(), '/');
            $items[] = [
                'name' => $route,
                'disabled' => !$page->isPage(),
                'label' => str_repeat('—', substr_count($route, '/')) . ' ' . $page->title(),
            ];
        }

        return $items;
    }
}
