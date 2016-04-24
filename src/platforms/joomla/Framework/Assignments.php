<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Joomla\CacheHelper;
use Gantry\Joomla\StyleHelper;
use Joomla\Utilities\ArrayHelper;

class Assignments
{
    use GantryTrait;

    protected $style_id;

    public function __construct($style_id)
    {
        $this->style_id = $style_id;
    }

    public function get()
    {
        return $this->getMenu();
    }

    public function set($data)
    {
        if (isset($data['assignment'])) {
            $this->setAssignment($data['assignment']);
        }
        if (isset($data['menu'])) {
            $this->setMenu($data['menu']);
        }
    }

    public function types()
    {
        return ['menu'];
    }

    public function getMenu()
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';
        $data = \MenusHelper::getMenuLinks();

        $userid = \JFactory::getUser()->id;

        $list = [];

        foreach ($data as $menu) {
            $items = [];
            foreach ($menu->links as $link) {
                $items[] = [
                    'name' => 'menu.' . $link->value,
                    'field' => ['id', 'link' . $link->value],
                    'value' => $link->template_style_id == $this->style_id,
                    'disabled' => $link->type != 'component' || $link->checked_out && $link->checked_out != $userid,
                    'label' => str_repeat('—', max(0, $link->level-1)) . ' ' . $link->text
                ];
            }
            $group = [
                'label' => $menu->title ?: $menu->menutype,
                'items' => $items
            ];

            $list[] = $group;
        }

        return $list;
    }

    public function setMenu($data)
    {
        $active = array_keys(array_filter($data, function($value) {return $value == 1; }));

        // Detect disabled template.
        $extension = \JTable::getInstance('Extension');

        $template = static::gantry()['theme.name'];
        if ($extension->load(array('enabled' => 0, 'type' => 'template', 'element' => $template, 'client_id' => 0))) {
            throw new \RuntimeException(\JText::_('COM_TEMPLATES_ERROR_SAVE_DISABLED_TEMPLATE'));
        }

        \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
        $style = \JTable::getInstance('Style', 'TemplatesTable');
        if (!$style->load($this->style_id) || $style->client_id != 0) {
            throw new \RuntimeException('Template style does not exist');
        }

        $user = \JFactory::getUser();
        $n = 0;

        if ($user->authorise('core.edit', 'com_menus')) {
            $db   = \JFactory::getDbo();
            $user = \JFactory::getUser();

            if (!empty($active) && is_array($active)) {
                ArrayHelper::toInteger($active);

                // Update the mapping for menu items that this style IS assigned to.
                $query = $db->getQuery(true)
                    ->update('#__menu')
                    ->set('template_style_id = ' . (int) $style->id)
                    ->where('id IN (' . implode(',', $active) . ')')
                    ->where('template_style_id != ' . (int) $style->id)
                    ->where('checked_out IN (0,' . (int) $user->id . ')');
                $db->setQuery($query);
                $db->execute();
                $n += $db->getAffectedRows();
            }

            // Remove style mappings for menu items this style is NOT assigned to.
            // If unassigned then all existing maps will be removed.
            $query = $db->getQuery(true)
                ->update('#__menu')
                ->set('template_style_id = 0');

            if (!empty($active)) {
                $query->where('id NOT IN (' . implode(',', $active) . ')');
            }

            $query->where('template_style_id = ' . (int) $style->id)
                ->where('checked_out IN (0,' . (int) $user->id . ')');
            $db->setQuery($query);
            $db->execute();

            $n += $db->getAffectedRows();
        }

        // Clean the cache.
        CacheHelper::cleanTemplates();

        return ($n > 0);
    }

    public function getAssignment()
    {
        $style = StyleHelper::getStyle($this->style_id);

        return $style->home;
    }

    public function setAssignment($value)
    {
        $options = $this->assignmentOptions();

        if (!isset($options[$value])) {
            throw new \RuntimeException('Invalid value for default assignment!', 400);
        }

        $style = StyleHelper::getStyle($this->style_id);
        $style->home = $value;

        if (!$style->check() || !$style->store()) {
            throw new \RuntimeException($style->getError());
        }

        // Clean the cache.
        CacheHelper::cleanTemplates();
    }

    public function assignmentOptions()
    {
        if ((string)(int) $this->style_id !== (string) $this->style_id) {
            return [];
        }

        $languages = \JHtml::_('contentlanguage.existing');

        $options = ['- Make Default -', 'All Languages'];
        foreach ($languages as $language) {
            $options[$language->value] = $language->text;
        }

        return $options;
    }
}
