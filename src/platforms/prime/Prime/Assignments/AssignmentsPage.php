<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Prime\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;
use Gantry\Prime\Pages;

class AssignmentsPage implements AssignmentsInterface
{
    public $type = 'page';
    public $priority = 1;

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        $rules = [[PAGE_PATH => $this->priority]];

        return $rules;
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
        $pages = new Pages();
        $items = [];
        foreach ($pages as $page => $file) {
            $path = explode('/', $page);
            $items[] = [
                'name' => $page,
                'field' => ['id', 'link-' . preg_replace('|[^a-zA-Z0-9-]|', '-', $page)],
                'label' => str_repeat('- ', count($path)-1) . ucwords(trim(end($path), '_'))
            ];
        }

        return $items;
    }
}
