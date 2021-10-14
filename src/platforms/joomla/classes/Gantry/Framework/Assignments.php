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

use Gantry\Component\Assignments\AbstractAssignments;
use Gantry\Joomla\CacheHelper;
use Gantry\Joomla\StyleHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Version;
use Joomla\Utilities\ArrayHelper;

/**
 * Class Assignments
 * @package Gantry\Framework
 */
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
        /** @var CMSApplication $application */
        $application = Factory::getApplication();

        if (!$application->isClient('site')) {
            return [];
        }

        // Get current template, style id and rules.
        $template = $application->getTemplate();
        $menu = $application->getMenu();
        $active = $menu ? $menu->getActive() : null;
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

    /**
     * @return array
     */
    public function types()
    {
        return ['menu', 'style'];
    }

    /**
     * @param array $data
     * @return bool
     */
    public function saveMenu($data)
    {
        $active = [];
        foreach ($data as $menutype => $items) {
            $active += array_filter($items, function($value) {return $value > 0; });

        }
        $active = array_keys($active);

        // Detect disabled template.
        $extension = Table::getInstance('Extension');

        $template = Gantry::instance()['theme.name'];
        if ($extension->load(array('enabled' => 0, 'type' => 'template', 'element' => $template, 'client_id' => 0))) {
            throw new \RuntimeException(Text::_('COM_TEMPLATES_ERROR_SAVE_DISABLED_TEMPLATE'));
        }

        $style = StyleHelper::getStyle();
        if (!$style->load($this->configuration) || $style->client_id) {
            throw new \RuntimeException('Template style does not exist');
        }

        /** @var CMSApplication $application */
        $application = Factory::getApplication();

        $user = $application->getIdentity();
        $n = 0;

        if ($user && $user->authorise('core.edit', 'com_menus')) {
            $checked_out_default = Version::MAJOR_VERSION < 4 ? 'checked_out = 0' : 'checked_out IS null';

            $db   = Factory::getDbo();

            if (!empty($active)) {
                ArrayHelper::toInteger($active);

                // Update the mapping for menu items that this style IS assigned to.
                $query = $db->getQuery(true)
                    ->update('#__menu')
                    ->set('template_style_id = ' . (int) $style->id)
                    ->where('id IN (' . implode(',', $active) . ')')
                    ->where('template_style_id != ' . (int) $style->id)
                    ->where('(checked_out = ' . (int) $user->id . ' OR ' . $checked_out_default . ')');
                $db->setQuery($query);
                $db->execute();
                $n += $db->getAffectedRows();
            }

            // Remove style mappings for menu items this style is NOT assigned to.
            // If unassigned then all existing maps will be removed.
            $query2 = $db->getQuery(true)
                ->update('#__menu')
                ->set('template_style_id = 0');

            if (!empty($active)) {
                $query2->where('id NOT IN (' . implode(',', $active) . ')');
            }

            $query2->where('template_style_id = ' . (int) $style->id)
                ->where('(checked_out = ' . (int) $user->id . ' OR ' . $checked_out_default . ')');
            $db->setQuery($query2);
            $db->execute();

            $n += $db->getAffectedRows();
        }

        // Clean the cache.
        CacheHelper::cleanTemplates();

        return ($n > 0);
    }

    /**
     * @return string
     */
    public function getAssignment()
    {
        $style = StyleHelper::getStyle($this->configuration);

        return $style->home;
    }

    /**
     * @param string $value
     */
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

    /**
     * @return array
     */
    public function assignmentOptions()
    {
        if ((string)(int) $this->configuration !== (string) $this->configuration) {
            return [];
        }

        $languages = HTMLHelper::_('contentlanguage.existing');

        $options = ['- Make Default -', 'All Languages'];
        foreach ($languages as $language) {
            $options[$language->value] = $language->text;
        }

        return $options;
    }
}
