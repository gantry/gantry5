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
use Grav\Common\Grav;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Plugin\AdminPlugin;

/**
 * Class AssignmentsType
 * @package Gantry\Grav\Assignments
 */
class AssignmentsType implements AssignmentsInterface
{
    public $type = 'type';
    public $priority = 2;

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        $grav = Grav::instance();

        /** @var PageInterface $page */
        $page = $grav['page'];

        $rules[$page->template()] = $this->priority;

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
                'label' => 'Page Types',
                'items' => $this->getItems()
        ];

        return [$list];
    }

    /**
     * @return array
     */
    protected function getItems()
    {
        $pageTypes = AdminPlugin::pagesTypes();

        $items = [];
        foreach ($pageTypes as $name => $title) {
            $items[] = [
                'name' => $name,
                'label' => ucfirst($title),
            ];
        }

        return $items;
    }
}
