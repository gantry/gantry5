<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Assignments\AbstractAssignments;
use Gantry\Joomla\CacheHelper;
use Gantry\Joomla\StyleHelper;
use Joomla\Utilities\ArrayHelper;

class Assignments extends AbstractAssignments
{
    protected $platform = 'Joomla';

    /**
     * Load all assignments.
     *
     * @return array
     */
    public function loadAssignments()
    {
        $app = \JFactory::getApplication();

        if (!$app->isSite()) {
            return [];
        }

        // Get current template, style id and rules.
        $template = $app->getTemplate();
        $active = $app->getMenu()->getActive();
        if ($active) {
            $style = (int) $active->template_style_id;
            $rules = [$active->menutype => [$active->id => true]];
        } else {
            $style = 0;
            $rules = [];
        }

        // Load saved assignments.
        $assignments = parent::loadAssignments();

        // Add missing template styles from Joomla.
        $styles = StyleHelper::loadStyles($template);
        $assignments += array_fill_keys(array_keys($styles), []);

        foreach ($assignments as $id => &$assignment) {
            // Add current menu item if it has been assigned to the style.
            $assignment['menu'] = $style === $id ? $rules : [];

            // Always add the current template style.
            $assignment['style'] =  ['id' => [$id => true]];
        }

        return $assignments;
    }

    /**
     * Save assignments for the configuration.
     *
     * @param array $data
     */
    public function save(array $data)
    {
        $data += ['assignment' => 0, 'menu' => []];

        // Joomla stores language and menu assignments by its own.
        $this->saveAssignment($data['assignment']);
        $this->saveMenu($data['menu']);
        unset($data['assignment'], $data['menu'], $data['style']);

        // Continue saving rest of the assignments.
        parent::save($data);
    }

    public function types()
    {
        return ['menu', 'style'];
    }

    public function saveMenu($data)
    {
        $active = [];
        foreach ($data as $menutype => $items) {
            $active += array_filter($items, function($value) {return $value > 0; });

        }
        $active = array_keys($active);

        // Detect disabled template.
        $extension = \JTable::getInstance('Extension');

        $template = Gantry::instance()['theme.name'];
        if ($extension->load(array('enabled' => 0, 'type' => 'template', 'element' => $template, 'client_id' => 0))) {
            throw new \RuntimeException(\JText::_('COM_TEMPLATES_ERROR_SAVE_DISABLED_TEMPLATE'));
        }

        \JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
        $style = \JTable::getInstance('Style', 'TemplatesTable');
        if (!$style->load($this->configuration) || $style->client_id != 0) {
            throw new \RuntimeException('Template style does not exist');
        }

        $user = \JFactory::getUser();
        $n = 0;

        if ($user->authorise('core.edit', 'com_menus')) {
            $db   = \JFactory::getDbo();
            $user = \JFactory::getUser();

            if (!empty($active)) {
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
        $style = StyleHelper::getStyle($this->configuration);

        return $style->home;
    }

    public function saveAssignment($value)
    {
        $options = $this->assignmentOptions();

        if (!isset($options[$value])) {
            throw new \RuntimeException('Invalid value for default assignment!', 400);
        }

        $style = StyleHelper::getStyle($this->configuration);
        $style->home = $value;

        if (!$style->check() || !$style->store()) {
            throw new \RuntimeException($style->getError());
        }

        // Clean the cache.
        CacheHelper::cleanTemplates();
    }

    public function assignmentOptions()
    {
        if ((string)(int) $this->configuration !== (string) $this->configuration) {
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
