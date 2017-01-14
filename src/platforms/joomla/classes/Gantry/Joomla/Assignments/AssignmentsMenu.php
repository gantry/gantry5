<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;

class AssignmentsMenu implements AssignmentsInterface
{
    public $type = 'menu';
    public $priority = 1;

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        $rules = [];

        $app = \JFactory::getApplication();
        if ($app->isSite()) {
            $active = $app->getMenu()->getActive();
            if ($active) {
                $menutype = $active->menutype;
                $id = $active->id;
                $rules = [$menutype => [$id => $this->priority]];
            }
        }

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
        require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';
        $data = \MenusHelper::getMenuLinks();

        $userid = \JFactory::getUser()->id;

        $list = [];

        foreach ($data as $menu) {
            $items = [];
            foreach ($menu->links as $link) {
                $items[] = [
                    'name' => $link->value,
                    'field' => ['id', 'link' . $link->value],
                    'value' => $link->template_style_id == $configuration,
                    'disabled' => $link->type != 'component' || $link->checked_out && $link->checked_out != $userid,
                    'label' => str_repeat('—', max(0, $link->level-1)) . ' ' . $link->text
                ];
            }
            $group = [
                'label' => $menu->title ?: $menu->menutype,
                'items' => $items
            ];

            $list[$menu->menutype] = $group;
        }

        return $list;
    }
}
