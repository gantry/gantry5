<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

/**
 * Class AssignmentsMenu
 * @package Gantry\Joomla\Assignments
 */
class AssignmentsMenu implements AssignmentsInterface
{
    /** @var string */
    public $type = 'menu';
    /** @var int */
    public $priority = 1;

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        $rules = [];

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        if ($application->isClient('site')) {
            $menu = $application->getMenu();
            $active = $menu ? $menu->getActive() : null;
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
        /** @var CMSApplication $application */
        $application = Factory::getApplication();

        $data = $this->getMenulinks();
        $user = $application->getIdentity();
        $userid = $user ? $user->id : 0;

        $list = [];

        $checked_out_default = Version::MAJOR_VERSION < 4 ? '0' : null;

        foreach ($data as $menu) {
            $items = [];
            foreach ($menu->links as $link) {
                $items[] = [
                    'name' => $link->value,
                    'field' => ['id', 'link' . $link->value],
                    'value' => $link->template_style_id == $configuration,
                    'disabled' => $link->type !== 'component' || ($link->checked_out !== $checked_out_default && $link->checked_out != $userid),
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

    /**
     * @return array
     */
    protected function getMenulinks()
    {
        // Works also in Joomla 4
        require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';

        return \MenusHelper::getMenuLinks();
    }
}
