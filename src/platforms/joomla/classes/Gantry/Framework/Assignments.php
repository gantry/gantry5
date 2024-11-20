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
use Joomla\CMS\Table\Extension;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * Class Assignments
 * @package Gantry\Framework
 */
class Assignments extends AbstractAssignments
{
    use DatabaseAwareTrait;

    /**
     * @var string
     */
    protected $platform = 'Joomla';

    /**
     * @param string|null $configuration
     * @param ?DatabaseInterface $db
     */
    public function __construct($configuration = null, ?DatabaseInterface $db = null)
    {
        parent::__construct($configuration);

        if ($db === null) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        }

        $this->setDatabase($db);
    }

    /**
     * Load all assignments.
     *
     * @return array
     */
    public function loadAssignments(): array
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return [];
        }

        // Get current template, style id and rules.
        $template = $app->getTemplate();
        $menu     = $app->getMenu();
        $active   = $menu ? $menu->getActive() : null;

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
        $styles      = StyleHelper::loadStyles($template);
        $assignments += \array_fill_keys(\array_keys($styles), []);

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
    public function save(array $data): void
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
    public function types(): array
    {
        return ['menu', 'style'];
    }

    /**
     * @param array $data
     * @return bool
     */
    public function saveMenu($data): bool
    {
        $active = [];
        foreach ($data as $menutype => $items) {
            $active += array_filter($items, function ($value): bool {
                return $value > 0;
            });
        }
        $active = \array_keys($active);

        // Detect disabled template.
        $extension = new Extension($this->getDatabase());

        $template = Gantry::instance()['theme.name'];

        if ($extension->load(['enabled' => 0, 'type' => 'template', 'element' => $template, 'client_id' => 0])) {
            throw new \RuntimeException(Text::_('COM_TEMPLATES_ERROR_SAVE_DISABLED_TEMPLATE'));
        }

        $style = StyleHelper::getStyle();

        if (!$style->load($this->configuration) || $style->client_id) {
            throw new \RuntimeException('Template style does not exist');
        }

        $user = Factory::getApplication()->getIdentity();
        $n = 0;

        if ($user && $user->authorise('core.edit', 'com_menus')) {
            $active = ArrayHelper::toInteger($active);

            $db = $this->getDatabase();

            if (!empty($active)) {
                // Update the mapping for menu items that this style IS assigned to.
                $query = $db->createQuery()
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('template_style_id') . ' = :newstyleid')
                    ->whereIn($db->quoteName('id'), $active)
                    ->where($db->quoteName('template_style_id') . ' != :styleid')
                    ->extendWhere(
                        'AND',
                        [
                            $db->quoteName('checked_out') . ' = :userid',
                            $db->quoteName('checked_out') . ' IS NULL',
                        ],
                        'OR'
                    )
                    ->bind(':newstyleid', $style->id, ParameterType::INTEGER)
                    ->bind(':styleid', $style->id, ParameterType::INTEGER)
                    ->bind(':userid', $style->id, ParameterType::INTEGER);

                $db->setQuery($query)->execute();

                $n += $db->getAffectedRows();
            }

            // Remove style mappings for menu items this style is NOT assigned to.
            // If unassigned then all existing maps will be removed.
            $query = $db->createQuery()
                ->update($db->quoteName('#__menu'))
                ->set($db->quoteName('template_style_id') . ' = 0');

            if (!empty($active)) {
                $query->whereNotIn($db->quoteName('id'), $active);
            }

            $query->where($db->quoteName('template_style_id') . ' = :styleid')
                ->extendWhere(
                    'AND',
                    [
                        $db->quoteName('checked_out') . ' = :userid',
                        $db->quoteName('checked_out') . ' IS NULL',
                    ],
                    'OR'
                )
                ->bind(':styleid', $style->id, ParameterType::INTEGER)
                ->bind(':userid', $user->id, ParameterType::INTEGER);

            $db->setQuery($query)->execute();

            $n += $db->getAffectedRows();
        }

        // Clean the cache.
        CacheHelper::cleanTemplates();

        return $n > 0;
    }

    /**
     * @return string
     */
    public function getAssignment(): string
    {
        $style = StyleHelper::getStyle($this->configuration);

        return $style->home;
    }

    /**
     * @param string $value
     * @return void
     */
    public function saveAssignment($value): void
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
    public function assignmentOptions(): array
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
