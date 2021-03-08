<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Grav\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;
use Grav\Common\Config\Config;
use Grav\Common\Flex\Types\Pages\PageIndex;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Framework\Flex\Flex;

/**
 * Class AssignmentsPage
 * @package Gantry\Grav\Assignments
 */
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

        /** @var Config $config */
        $config = $grav['config'];

        /** @var Uri $uri */
        $uri = $grav['uri'];

        $route = trim($uri->path(), '/');
        $home = trim($config->get('system.home.alias', '/home'), '/');
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

    /**
     * @return array
     */
    protected function getItems()
    {
        $grav = Grav::instance();

        /** @var Flex $flex */
        $flex = $grav['flex'];
        $directory = $flex->getDirectory('pages');
        if (!$directory) {
            throw new \RuntimeException('Flex Pages are required for Gantry to work!');
        }
        /** @var PageIndex $pages */
        $pages = $directory->getCollection();
        $pages = $pages->routable();

        $items = [];
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
